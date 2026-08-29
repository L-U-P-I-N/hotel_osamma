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
     * عن سعر باقي الليالي (وكان أكثر من ليلة) نُقسّم الإقامة إلى ليالٍ فردية وفق
     * حدود الساعة 1 ظهراً الفعلية بين لحظتي الوصول والخروج (نفس خوارزمية
     * Reservation::billableNightsFor بالضبط) — فوصول مبكر قبل 1 ظهراً يجعل الليلة
     * الأولى جزئية بنفس يوم الوصول بدل الظهور كيوم كامل خاطئ. إن كان السعر موحّداً
     * فترة واحدة تكفي (لا داعي للتقسيم لغياب فرق الأسعار).
     */
    public function recordInitial(Reservation $reservation, float $firstNight, float $restPrice, int $nights, ?int $userId = null, ?int $shiftId = null): void
    {
        $nights = max(1, $nights);

        if ($nights > 1 && round($firstNight, 2) != round($restPrice, 2)) {
            $periods = Reservation::splitBillingPeriods(
                $reservation->check_in_date, $reservation->check_out_date,
                $reservation->check_out_time, $reservation->check_in_time
            );
            foreach ($periods as $i => $period) {
                $price = $i === 0 ? $firstNight : $restPrice;
                $this->create($reservation, 'initial', $period['start'], $period['end'], 1, $price, round($price, 2), $userId, $shiftId);
            }
        } else {
            // سعر موحّد لكل ليالي الحجز الأولي (أو ليلة واحدة/يوم الوصول) — فترة واحدة
            $start = $reservation->check_in_date->copy();
            $end   = $reservation->check_out_date->copy();
            if ($end->lt($start)) {
                $end = $start->copy();
            }
            $price  = $nights === 1 ? $firstNight : $restPrice;
            $amount = round($firstNight + max(0, $nights - 1) * $restPrice, 2);
            $this->create($reservation, 'initial', $start, $end, $nights, $price, $amount, $userId, $shiftId);
        }
    }

    /**
     * يسجّل فترة تجديد واحدة (يدوي أو تلقائي) — تُضاف كسطر مستقل بتاريخها وسعرها.
     * تُربط بالوردية المفتوحة وقت التجديد (إن وُجدت) حتى يُمنَع لاحقاً تعديل/حذف
     * تجديدٍ يخصّ وردية أُقفلت بالفعل، فلا تتغيّر أرقام عمل وردية سابقة منتهية.
     */
    public function recordRenewal(Reservation $reservation, $start, $end, int $nights, float $price, float $amount, ?int $userId = null, ?int $shiftId = null): void
    {
        $this->create(
            $reservation,
            'renewal',
            Carbon::parse($start),
            Carbon::parse($end),
            max(1, $nights),
            $price,
            round($amount, 2),
            $userId,
            $shiftId
        );
    }

    /**
     * يعيد بناء كل فترات الحجز من الصفر اعتماداً على أسعار الغرفة الحالية — يُستخدم
     * عند تعديل الحجز (الذي يعيد حساب إجمالي الغرفة كـ «ليلة أولى + باقي الليالي»).
     *
     * لا يُسمح بذلك إن كانت هناك فترة مرتبطة بوردية مُقفلة: إعادة البناء تحذف كل
     * الفترات، فتُغيّر أرقام عملٍ سابق مُصفّى — وهو ما تمنعه أصلاً شاشتا تعديل/حذف
     * الفترة (isLocked). كان هذا المسار ينسف تلك الحماية بالكامل.
     */
    public function rebuildFromCurrentPricing(Reservation $reservation, float $firstNight, float $restPrice, int $nights, ?int $userId = null): void
    {
        if ($this->hasLockedSegments($reservation)) {
            throw new \RuntimeException(
                'لا يمكن إعادة تسعير كامل الإقامة لأن بعض فتراتها تخصّ ورديات أُقفلت بالفعل. '
                . 'عدّل سعر الفترة المطلوبة وحدها من تفصيل فترات الغرفة، أو استخدم «تغيير السعر من تاريخ».'
            );
        }

        $reservation->segments()->delete();
        $this->recordInitial($reservation, $firstNight, $restPrice, $nights, $userId);
    }

    /** هل بالحجز فترة مرتبطة بوردية أُقفلت (فلا يجوز المساس بها)؟ */
    public function hasLockedSegments(Reservation $reservation): bool
    {
        return $reservation->segments()->with('shift')->get()->contains(fn(ReservationSegment $s) => $s->isLocked());
    }

    /** مجموع مبالغ فترات الغرفة (= إجمالي الغرفة قبل الخصم متى تطابقت). */
    public function sumAmount(Reservation $reservation): float
    {
        return round((float) $reservation->segments()->sum('amount'), 2);
    }

    /**
     * يطبّق سعر ليلة جديداً اعتباراً من تاريخ معيّن فصاعداً فقط — الليالي المنقضية
     * قبل ذلك التاريخ تبقى بسعرها الأصلي. هذا هو المطلوب عند منح النزيل تخفيضاً
     * للّيالي القادمة دون إعادة كتابة ما احتُسب وسُجِّل بالفعل.
     *
     * الفترة التي يقع التاريخ في منتصفها تُقسَم إلى فترتين: ما قبل التاريخ بسعرها
     * القديم، وما بعده بالسعر الجديد.
     *
     * @return array{updated:int, split:int, delta:float} ملخص ما تغيّر
     */
    public function repriceFrom(Reservation $reservation, Carbon $fromDate, float $newPrice, ?int $userId = null): array
    {
        $from = $fromDate->copy()->startOfDay();
        $updated = 0;
        $split = 0;
        $delta = 0.0;

        $segments = $reservation->segments()->with('shift')->orderBy('start_date')->get();

        foreach ($segments as $segment) {
            $start = $segment->start_date->copy()->startOfDay();
            $end   = $segment->end_date->copy()->startOfDay();

            // فترة انتهت قبل التاريخ المطلوب — لا تُمسّ إطلاقاً
            if ($end->lte($from)) {
                continue;
            }
            // فترة مُقفلة بوردية مُصفّاة — تُترك كما هي (لا نغيّر عمل وردية منتهية)
            if ($segment->isLocked()) {
                continue;
            }

            $oldPrice = (float) $segment->price_per_night;

            if ($start->gte($from)) {
                // الفترة كلها ضمن المدى الجديد
                if (round($oldPrice, 2) === round($newPrice, 2)) {
                    continue;
                }
                $newAmount = round($newPrice * $segment->nights, 2);
                $delta += round($newAmount - (float) $segment->amount, 2);
                $segment->update(['price_per_night' => $newPrice, 'amount' => $newAmount]);
                $updated++;
                continue;
            }

            // التاريخ يقع داخل الفترة → نقسمها: ما قبله بسعره القديم، وما بعده بالجديد
            $nightsBefore = $start->diffInDays($from);
            $nightsAfter  = $segment->nights - $nightsBefore;
            if ($nightsBefore < 1 || $nightsAfter < 1) {
                continue;
            }

            $beforeAmount = round($oldPrice * $nightsBefore, 2);
            $afterAmount  = round($newPrice * $nightsAfter, 2);
            $delta += round(($beforeAmount + $afterAmount) - (float) $segment->amount, 2);

            $segment->update([
                'end_date' => $from->toDateString(),
                'nights'   => $nightsBefore,
                'amount'   => $beforeAmount,
            ]);

            $this->create($reservation, $segment->type, $from->copy(), $end->copy(), $nightsAfter, $newPrice, $afterAmount, $userId, $segment->shift_id);
            $split++;
        }

        return ['updated' => $updated, 'split' => $split, 'delta' => round($delta, 2)];
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

    private function create(Reservation $reservation, string $type, Carbon $start, Carbon $end, int $nights, float $price, float $amount, ?int $userId, ?int $shiftId = null): void
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
            'shift_id'        => $shiftId,
        ]);
    }
}
