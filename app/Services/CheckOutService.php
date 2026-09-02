<?php
namespace App\Services;

use App\Helpers\StorageHelper;
use App\Models\Expense;
use App\Models\ExtraCharge;
use App\Models\InspectionImage;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\RoomInspection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CheckOutService
{
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
                            ['account_code' => '5100', 'debit' => $compensationAmount],
                            ['account_code' => '1200', 'credit' => $compensationAmount],
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
