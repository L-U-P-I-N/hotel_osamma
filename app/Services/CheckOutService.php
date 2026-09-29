<?php
namespace App\Services;

use App\Helpers\StorageHelper;
use App\Models\Expense;
use App\Models\ExtraCharge;
use App\Models\GuestCredit;
use App\Models\InspectionImage;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\RoomInspection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckOutService
{
    /**
     * حساب المغادرة المبكرة: نزيل حجز (ودفع) ليالٍ أكثر مما أقام. يُرجِع تفصيل
     * التسوية المقترحة، أو null إن لم تكن هناك ليالٍ غير مستهلكة.
     *
     * الفترات المحاسبية (segments) لا تُمَس: قد تكون إحداها مرتبطة بوردية أُقفلت
     * فعلاً، وإعادة كتابتها تُغيّر أرقام عملٍ سابقٍ مُصفّى. لذا تُسجَّل الليالي
     * غير المستهلكة كخصم على الحجز — فيبقى إجمالي الغرفة قبل الخصم (gross_total)
     * مطابقاً لمجموع الفترات كما هو، ويهبط المستحق وحده.
     *
     * @return array{planned_nights:int,actual_nights:int,unused_nights:int,savings:float,new_total:float,new_discount:float,refundable:float,new_balance:float}|null
     */
    public function earlyDepartureQuote(Reservation $reservation, $departureAt = null): ?array
    {
        $departure = $departureAt ? \Carbon\Carbon::parse($departureAt) : now();

        $plannedNights = max(1, $reservation->nights);
        $actualNights  = Reservation::billableNightsFor(
            $reservation->check_in_date,
            $departure->toDateString(),
            $departure->format('H:i'),
            $reservation->check_in_time
        );
        $actualNights = max(1, min($actualNights, $plannedNights));
        $unusedNights = $plannedNights - $actualNights;

        if ($unusedNights <= 0) {
            return null;
        }

        // نفس اشتقاق الأسعار المستخدَم في تعديل تواريخ الإقامة: سعر محفوظ إن
        // وُجد، وإلا يُشتقّ من الإجمالي الفعلي لا من سعر الغرفة الافتراضي (فقد
        // يكون الحجز مسعَّراً بسعر متفاوَض عليه).
        $firstNightPrice = $reservation->first_night_price !== null
            ? (float) $reservation->first_night_price
            : round((float) $reservation->gross_total / $plannedNights, 2);
        $renewalPrice = $reservation->renewal_price_per_night !== null
            ? (float) $reservation->renewal_price_per_night
            : $firstNightPrice;

        $grossOld   = (float) $reservation->gross_total;
        $netStayOld = round($grossOld - (float) $reservation->discount_amount, 2);

        $newGross   = round($firstNightPrice + max(0, $actualNights - 1) * $renewalPrice, 2);
        $netStayNew = max(0, round($newGross - $reservation->discountAmountFor($newGross), 2));

        $savings = round(max(0, $netStayOld - $netStayNew), 2);

        if ($savings <= 0) {
            return null;
        }

        $newTotal    = round(max(0, (float) $reservation->total_amount - $savings), 2);
        $newDiscount = round((float) $reservation->discount_amount + $savings, 2);
        $paid        = (float) $reservation->paid_amount;

        return [
            'planned_nights' => $plannedNights,
            'actual_nights'  => $actualNights,
            'unused_nights'  => $unusedNights,
            'savings'        => $savings,
            'new_total'      => $newTotal,
            'new_discount'   => $newDiscount,
            // ما دفعه النزيل فوق المستحق الجديد — يُرحَّل رصيداً باسمه أو يُصرف له
            'refundable'     => round(max(0, $paid - $newTotal), 2),
            'new_balance'    => round(max(0, $newTotal - $paid), 2),
        ];
    }

    /**
     * يطبّق تسوية المغادرة المبكرة: يُنزل المستحق إلى قيمة الليالي المُقامة فعلاً،
     * ثم يرحّل ما دفعه النزيل زائداً إلى رصيد باسمه (أو يصرفه له نقداً الآن).
     * يُنفَّذ داخل معاملة الخروج نفسها.
     */
    private function applyEarlyDeparture(Reservation $reservation, array $data, User $user): ?array
    {
        // الحساب يُعاد على الخادم دائماً — قيم النموذج للعرض فقط
        $quote = $this->earlyDepartureQuote($reservation);

        if (!$quote) {
            return null;
        }

        $old = $reservation->only(['total_amount', 'discount_amount', 'discount_type', 'discount_value']);

        $reason = 'مغادرة مبكرة: ' . $quote['unused_nights'] . ' من '
            . $quote['planned_nights'] . ' ليلة لم تُستهلك';

        // النوع يُحوَّل إلى مبلغ ثابت: الإقامة انتهت فلا معنى لإعادة احتساب نسبة
        // مئوية لاحقاً، والمبلغ الثابت يحفظ الخصم كما هو عند أي تعديل تالٍ.
        $reservation->discount_amount = $quote['new_discount'];
        $reservation->discount_type   = 'fixed';
        $reservation->discount_value  = $quote['new_discount'];
        $reservation->discount_reason = trim(($reservation->discount_reason ? $reservation->discount_reason . ' + ' : '') . $reason);
        $reservation->total_amount    = $quote['new_total'];
        $reservation->save();

        $reservation->refresh()->recalculatePaidAmount();

        if ($quote['refundable'] > 0) {
            $creditService = app(\App\Services\GuestCreditService::class);
            $credit = $creditService->carry($reservation, $quote['refundable'], $reason, $user);

            // صرف فوري: المبلغ يخرج من صندوق الوردية الآن بدل أن يبقى رصيداً
            if (($data['credit_action'] ?? 'carry') === 'payout') {
                $creditService->settle($credit, [
                    'settlement_method' => $data['credit_method'] ?? 'cash',
                    'settlement_notes'  => 'صُرف للنزيل لحظة تسجيل الخروج',
                ], $user);
            }

            $reservation->refresh();
        }

        AuditLogService::log('update', $reservation, $old, [
            'action'        => 'early_departure_settled',
            'unused_nights' => $quote['unused_nights'],
            'savings'       => $quote['savings'],
            'refundable'    => $quote['refundable'],
            'credit_action' => $data['credit_action'] ?? 'carry',
        ], $user);

        return $quote;
    }

    public function processCheckOut(Reservation $reservation, array $data, User $user): Reservation
    {
        return DB::transaction(function () use ($reservation, $data, $user) {
            // 0. دَين المشتريات (بقالة) منفصل عن صندوق الفندق — يجب تحصيله قبل
            // الخروج (أو تركه كدَين عند «غادر دون سداد»). تحصيله توثيقي فقط:
            // يُعلَّم settled_at دون إنشاء مستلمة أو ربط بوردية أو تقرير فندق.
            $purchasesDebt    = round((float) $reservation->extraCharges()
                ->where('in_hotel_total', false)->whereNull('settled_at')->sum('amount'), 2);
            $collectPurchases = !empty($data['collect_purchases']);
            $leftUnpaid       = !empty($data['left_unpaid']);

            if ($purchasesDebt > 0 && !$collectPurchases && !$leftUnpaid) {
                throw new \RuntimeException('يوجد دَين مشتريات (بقالة) غير محصَّل — حصِّله قبل الخروج أو أشِر إلى «غادر دون سداد».');
            }

            if ($purchasesDebt > 0 && $collectPurchases) {
                ExtraCharge::where('reservation_id', $reservation->id)
                    ->where('in_hotel_total', false)
                    ->whereNull('settled_at')
                    ->update(['settled_at' => now(), 'settled_by' => $user->id]);
            }

            // 0.5 المغادرة المبكرة تُسوّى قبل أي حساب آخر: هي التي تحدّد المستحق
            // الفعلي، وبقية الخطوات (الأضرار، الدفع، حالة السداد) تبنى عليه.
            if (!empty($data['early_departure'])) {
                $this->applyEarlyDeparture($reservation, $data, $user);
            }

            // a. Create RoomInspection
            $inspection = RoomInspection::create([
                'reservation_id' => $reservation->id,
                'inspected_by' => $user->id,
                'has_damage' => $data['has_damage'] ?? false,
                'damage_description' => $data['damage_description'] ?? null,
                'compensation_amount' => $data['compensation_amount'] ?? 0,
                'compensation_status' => 'none',
                'inspection_date' => now(),
            ]);

            // b. Save inspection images
            if (!empty($data['inspection_images'])) {
                foreach ($data['inspection_images'] as $image) {
                    $path = $image->store('inspection_images', 'private');
                    InspectionImage::create([
                        'room_inspection_id' => $inspection->id,
                        'image_path' => $path,
                    ]);
                }
            }

            // c. Handle damage — add to total_amount, mark compensation pending until payment below
            $hasDamage = $data['has_damage'] ?? false;
            $compensationAmount = $hasDamage ? ($data['compensation_amount'] ?? 0) : 0;

            if ($hasDamage && $compensationAmount > 0) {
                $damageCharge = ExtraCharge::create([
                    'reservation_id'     => $reservation->id,
                    'room_inspection_id' => $inspection->id,
                    'added_by'           => $user->id,
                    'type'               => 'damage',
                    'description'        => $data['damage_description'] ?? 'تعويض أضرار',
                    'amount'             => $compensationAmount,
                    'charge_date'        => now(),
                ]);

                Expense::create([
                    'amount'             => $compensationAmount,
                    'currency'           => $data['currency'] ?? 'YER',
                    'category'           => 'maintenance',
                    'description'        => 'أضرار غرفة ' . ($reservation->room->room_number ?? '') . ' — ' . ($data['damage_description'] ?? 'تعويض أضرار'),
                    'expense_date'       => now()->toDateString(),
                    'paid_by'            => $user->id,
                    'shift_id'           => null,
                    'room_inspection_id' => $inspection->id,
                ]);

                $reservation->increment('total_amount', $compensationAmount);
                $inspection->update(['compensation_status' => 'pending']);

                // ريال يمني فقط حالياً — الضرر مصروف صيانة يموَّل بزيادة دَين
                // النزيل (لا خروج نقدية الآن)؛ عند تحصيله يُرحَّل كدفعة عادية.
                if (($data['currency'] ?? 'YER') === 'YER') {
                    app(JournalService::class)->post(
                        now()->toDateString(),
                        'تعويض أضرار — حجز #' . $reservation->id,
                        ExtraCharge::class,
                        $damageCharge->id,
                        [
                            ['account_code' => '1210', 'debit' => $compensationAmount],
                            ['account_code' => '4660', 'credit' => $compensationAmount],
                        ],
                        $user->id
                    );
                }
            }

            // d. Settle combined balance (reservation balance + damage compensation in one payment)
            if (!empty($data['remaining_payment']) && $data['remaining_payment'] > 0) {
                $bankReceiptPath = null;
                if (!empty($data['remaining_bank_receipt'])) {
                    // نفس القرص الخاص الذي يقرأ منه عارض السند (تفادي 404)
                    $bankReceiptPath = StorageHelper::store($data['remaining_bank_receipt'], 'bank_receipts');
                }

                // ربط دفعة الخروج بالوردية المفتوحة للموظف حتى تظهر عند إقفالها
                $shiftService = app(ShiftService::class);
                $shift = $shiftService->getActiveShift($user);

                Payment::create([
                    'reservation_id' => $reservation->id,
                    'shift_id' => $shift?->id,
                    'received_by' => $user->id,
                    'amount' => $data['remaining_payment'],
                    'currency' => 'YER',
                    'method' => $data['remaining_method'] ?? 'cash',
                    'bank_receipt_path' => $bankReceiptPath,
                    'notes' => $data['payment_notes'] ?? null,
                    'payment_date' => now(),
                    'type' => 'reservation',
                ]);

                $reservation->refresh()->recalculatePaidAmount();

                if ($shift) {
                    $shiftService->computeTotals($shift);
                }

                // Mark compensation as paid if damage was included in this combined payment
                if ($hasDamage && $compensationAmount > 0) {
                    $inspection->update(['compensation_status' => 'paid']);
                }
            }

            // e. Update Reservation
            $reservation->refresh();

            // نوقف التجديد التلقائي عند الخروج (حتى لا يُحتسب أي يوم جديد بعد المغادرة)
            $updates = [
                'status'           => 'checked_out',
                'actual_check_out' => now(),
                'checked_out_by'   => $user->id,
                'auto_renew'       => false,
                // ملاحظة الخروج تظهر في كشوف النزلاء (الموجودين والمغادرين)
                'checkout_notes'   => $this->nullIfBlank($data['checkout_notes'] ?? null),
            ];

            // النزيل غادر دون سداد (هروب): نُسجّل الخروج مع بقاء الدين ونوثّق ذلك
            $remainingDebt = round((float) $reservation->total_amount - (float) $reservation->paid_amount, 2);
            if (!empty($data['left_unpaid']) && $remainingDebt > 0) {
                $debtNote = '[غادر النزيل دون سداد — دَين متبقٍ: ' . number_format($remainingDebt, 0) . ' ر.ي]';
                $updates['notes'] = $reservation->notes
                    ? $reservation->notes . "\n" . $debtNote
                    : $debtNote;
            }

            $reservation->update($updates);
            $reservation->updatePaymentStatus();

            // f. Update Room status — always under_inspection after checkout; staff changes manually
            $newRoomStatus = 'under_inspection';
            $reservation->room->update(['status' => $newRoomStatus]);

            // Free the linked room too (suite B or apartment partner)
            if ($reservation->linked_room_id) {
                \App\Models\Room::where('id', $reservation->linked_room_id)
                    ->update(['status' => $newRoomStatus]);
            }

            // g. Log audit
            AuditLogService::log('update', $reservation, ['status' => 'checked_in'], ['status' => 'checked_out'], $user);

            return $reservation->fresh();
        });
    }

    /** نصّ فارغ أو مسافات لا يُحفظ ملاحظةً — يبقى الحقل null فتُقرأ الكشوف نظيفة. */
    private function nullIfBlank(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }

    /**
     * تراجع عن تسجيل خروج تمّ بالغلط لنزيل ما زال موجوداً فعلياً — يعيد الحجز
     * لحالة "مسجل دخول" ويعيد الغرفة "مشغولة". لا يمسّ أي سجلات مالية أنشئت
     * وقت الخروج (دفعة متبقية، أضرار...)؛ هذه يراجعها الموظف يدوياً إن وُجدت.
     */
    public function undoCheckOut(Reservation $reservation, User $user): Reservation
    {
        if ($reservation->status !== 'checked_out') {
            throw new \RuntimeException('لا يمكن التراجع — الحجز ليس في حالة "مسجل خروج".');
        }

        // نافذة التراجع محدودة بـ3 ساعات من لحظة الخروج الفعلي — بعدها يُعتبر
        // الخروج نهائياً (تفادياً لتعديل حجوزات قديمة أُقفلت فعلياً محاسبياً).
        if (!$reservation->actual_check_out || $reservation->actual_check_out->addHours(3)->isPast()) {
            throw new \RuntimeException('انتهت مهلة التراجع عن الخروج (3 ساعات من وقت الخروج الفعلي).');
        }

        return DB::transaction(function () use ($reservation, $user) {
            $reservation->update([
                'status'           => 'checked_in',
                'actual_check_out' => null,
                'checked_out_by'   => null,
            ]);

            $reservation->room->update(['status' => 'occupied']);
            if ($reservation->linked_room_id) {
                \App\Models\Room::where('id', $reservation->linked_room_id)->update(['status' => 'occupied']);
            }

            AuditLogService::log('update', $reservation, ['status' => 'checked_out'], ['status' => 'checked_in'], $user);

            return $reservation->fresh();
        });
    }
}
