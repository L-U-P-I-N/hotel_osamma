<?php
namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationSegment;
use Carbon\Carbon;

/**
 * يبني ويحافظ على سجل فترات محاسبة الغرفة (الحجز الأولي + التجديدات) لعرض تفصيل
 * أسعار الغرفة بدل متوسط الليلة. الفترات تُجمَع دائماً مطابقةً لإجمالي الغرفة قبل
 * الخصم (gross_total).
 */
class ReservationSegmentService
{
    /** هامش تقريب مسموح (ريال) عند التحقق من تطابق مجموع الفترات مع إجمالي الغرفة. */
    private const TOLERANCE = 1.0;

    /**
     * يسجّل فترة/فترات الحجز الأولي عند تسجيل الدخول. إن اختلف سعر الليلة الأولى
     * عن سعر باقي الليالي (وكان أكثر من ليلة) نُنشئ فترتين منفصلتين، وإلا فترة واحدة.
     */
    public function recordInitial(Reservation $reservation, float $firstNight, float $restPrice, int $nights, ?int $userId = null): void
    {
        $nights = max(1, $nights);
        $start  = $reservation->check_in_date->copy();
        // نهاية الحجز الأولي = تاريخ الخروج الفعلي، لا (الوصول + الليالي): فالمبيت
        // نفس اليوم (وصول فجراً وخروج ظهراً) يُعرَض 11→11 لا 11→12، وأي تجديد لاحق
        // يمدّد يوماً كاملاً حتى الخروج.
        $end = $reservation->check_out_date->copy();
        if ($end->lt($start)) {
            $end = $start->copy();
        }

        if ($nights > 1 && round($firstNight, 2) != round($restPrice, 2)) {
            // فترة الليلة الأولى بسعرها الخاص
            $firstEnd = $start->copy()->addDay();
            if ($firstEnd->gt($end)) {
                $firstEnd = $end->copy();
            }
            $this->create($reservation, 'initial', $start, $firstEnd, 1, $firstNight, $firstNight, $userId);
            // باقي ليالي الحجز الأولي بسعر الليلة العادي
            $rest = $nights - 1;
            $this->create($reservation, 'initial', $firstEnd, $end, $rest, $restPrice, round($rest * $restPrice, 2), $userId);
        } else {
            // سعر موحّد لكل ليالي الحجز الأولي (أو ليلة واحدة/يوم الوصول)
            $price  = $nights === 1 ? $firstNight : $restPrice;
            $amount = round($firstNight + max(0, $nights - 1) * $restPrice, 2);
            $this->create($reservation, 'initial', $start, $end, $nights, $price, $amount, $userId);
        }
    }

    /**
     * يسجّل فترة تجديد واحدة (يدوي أو تلقائي) — تُضاف كسطر مستقل بتاريخها وسعرها.
     */
    public function recordRenewal(Reservation $reservation, $start, $end, int $nights, float $price, float $amount, ?int $userId = null): void
    {
        $this->create(
            $reservation,
            'renewal',
            Carbon::parse($start),
            Carbon::parse($end),
            max(1, $nights),
            $price,
            round($amount, 2),
            $userId
        );
    }

    /**
     * يعيد بناء كل فترات الحجز من الصفر اعتماداً على أسعار الغرفة الحالية — يُستخدم
     * عند تعديل الحجز (الذي يعيد حساب إجمالي الغرفة كـ «ليلة أولى + باقي الليالي»).
     */
    public function rebuildFromCurrentPricing(Reservation $reservation, float $firstNight, float $restPrice, int $nights, ?int $userId = null): void
    {
        $reservation->segments()->delete();
        $this->recordInitial($reservation, $firstNight, $restPrice, $nights, $userId);
    }

    /**
     * يعيد بناء الفترات لحجزٍ قائم من تاريخه: الحجز الأولي + التجديدات المستخرَجة من
     * الملاحظات (يدوية وتلقائية). يُستخدم لتعبئة الحجوزات الحالية مرّةً واحدة. لا
     * يحفظ شيئاً إن تعذّر التوفيق بين المجموع وإجمالي الغرفة (يُترك للعرض الاحتياطي).
     *
     * @return bool هل أُعيد البناء بنجاح (وتطابق المجموع)؟
     */
    public function backfillFromHistory(Reservation $reservation): bool
    {
        $roomGross = (float) $reservation->gross_total;
        if ($roomGross <= 0) {
            return false;
        }

        // استخراج التجديدات من الملاحظات: يدوي «[تجديد +N ليلة بسعر X ر.ي/ليلة]»
        // وتلقائي «[تجديد تلقائي +N ليالٍ بسعر X ر.ي/ليلة — حتى ...]».
        $renewals = [];
        if ($reservation->notes) {
            if (preg_match_all('/تجديد[^\[\]]*?\+\s*(\d+)[^\[\]]*?بسعر\s*([\d,]+)/u', $reservation->notes, $m, PREG_SET_ORDER)) {
                foreach ($m as $match) {
                    $n     = (int) $match[1];
                    $price = (float) str_replace(',', '', $match[2]);
                    if ($n > 0) {
                        $renewals[] = ['nights' => $n, 'price' => $price, 'amount' => round($n * $price, 2)];
                    }
                }
            }
        }

        $renewalNights = array_sum(array_column($renewals, 'nights'));
        $renewalAmount = round(array_sum(array_column($renewals, 'amount')), 2);

        // الحجز الأولي = المتبقي من إجمالي الغرفة بعد خصم مبالغ التجديدات
        $initialAmount = round($roomGross - $renewalAmount, 2);
        $totalNights   = max(1, (int) $reservation->nights);
        $initialNights = max(1, $totalNights - $renewalNights);

        // إن كانت مبالغ التجديدات وحدها تفوق إجمالي الغرفة فالبيانات غير متّسقة
        if ($initialAmount < -self::TOLERANCE) {
            return false;
        }
        $initialAmount = max(0, $initialAmount);

        $initialPrice = $reservation->first_night_price !== null
            ? (float) $reservation->first_night_price
            : round($initialAmount / $initialNights, 2);

        // نبني الفترات بحيث ينتهي آخر تجديد عند تاريخ الخروج بالضبط (تسلسل مثبَّت من
        // الخروج للخلف): فيصبح الحجز الأولي في يوم الوصول نفسه (11→11 لمن جاء فجراً
        // وخرج ظهراً)، ثم كل تجديد يوماً كاملاً — دون تجاوز تاريخ الخروج.
        $checkIn    = $reservation->check_in_date->copy();
        $initialEnd = $reservation->check_out_date->copy()->subDays($renewalNights);
        if ($initialEnd->lt($checkIn)) {
            $initialEnd = $checkIn->copy(); // بيانات غير متّسقة: نُبقي الأولي في يوم الوصول
        }

        $rows   = [];
        $rows[] = [
            'type' => 'initial', 'start' => $checkIn->copy(), 'end' => $initialEnd->copy(),
            'nights' => $initialNights, 'price' => $initialPrice, 'amount' => $initialAmount,
        ];
        $cursor = $initialEnd->copy();

        foreach ($renewals as $r) {
            $rows[] = [
                'type' => 'renewal', 'start' => $cursor->copy(), 'end' => $cursor->copy()->addDays($r['nights']),
                'nights' => $r['nights'], 'price' => $r['price'], 'amount' => $r['amount'],
            ];
            $cursor = $cursor->copy()->addDays($r['nights']);
        }

        // تحقّق من التطابق قبل الحفظ
        $sum = round(array_sum(array_column($rows, 'amount')), 2);
        if (abs($sum - $roomGross) > self::TOLERANCE) {
            return false;
        }

        $reservation->segments()->delete();
        foreach ($rows as $row) {
            $this->create($reservation, $row['type'], $row['start'], $row['end'], $row['nights'], $row['price'], $row['amount'], null);
        }

        return true;
    }

    /**
     * هل مجموع الفترات المسجّلة يطابق إجمالي الغرفة قبل الخصم؟ (لضمان عرض أرقام
     * متّسقة، وإلا يسقط العرض إلى البديل).
     */
    public function reconciles(Reservation $reservation): bool
    {
        $segments = $reservation->relationLoaded('segments') ? $reservation->segments : $reservation->segments()->get();
        if ($segments->isEmpty()) {
            return false;
        }
        $sum = round((float) $segments->sum('amount'), 2);
        return abs($sum - (float) $reservation->gross_total) <= self::TOLERANCE;
    }

    private function create(Reservation $reservation, string $type, Carbon $start, Carbon $end, int $nights, float $price, float $amount, ?int $userId): void
    {
        ReservationSegment::create([
            'reservation_id'  => $reservation->id,
            'type'            => $type,
            'start_date'      => $start->toDateString(),
            'end_date'        => $end->toDateString(),
            'nights'          => max(1, $nights),
            'price_per_night' => round($price, 2),
            'amount'          => round($amount, 2),
            'created_by'      => $userId,
        ]);
    }
}
