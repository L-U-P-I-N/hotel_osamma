<?php
namespace App\Services;

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
                ExtraCharge::create([
                    'reservation_id' => $reservation->id,
                    'added_by'       => $user->id,
                    'type'           => 'damage',
                    'description'    => $data['damage_description'] ?? 'تعويض أضرار',
                    'amount'         => $compensationAmount,
                    'charge_date'    => now(),
                ]);

                Expense::create([
                    'amount'       => $compensationAmount,
                    'currency'     => $data['currency'] ?? 'YER',
                    'category'     => 'maintenance',
                    'description'  => 'أضرار غرفة ' . ($reservation->room->room_number ?? '') . ' — ' . ($data['damage_description'] ?? 'تعويض أضرار'),
                    'expense_date' => now()->toDateString(),
                    'paid_by'      => $user->id,
                    'shift_id'     => null,
                ]);

                $reservation->increment('total_amount', $compensationAmount);
                $inspection->update(['compensation_status' => 'pending']);
            }

            // d. Settle combined balance (reservation balance + damage compensation in one payment)
            if (!empty($data['remaining_payment']) && $data['remaining_payment'] > 0) {
                $bankReceiptPath = null;
                if (!empty($data['remaining_bank_receipt'])) {
                    $bankReceiptPath = $data['remaining_bank_receipt']->store('bank_receipts', 'private');
                }

                Payment::create([
                    'reservation_id' => $reservation->id,
                    'received_by' => $user->id,
                    'amount' => $data['remaining_payment'],
                    'currency' => $data['currency'] ?? 'YER',
                    'method' => $data['remaining_method'] ?? 'cash',
                    'bank_receipt_path' => $bankReceiptPath,
                    'payment_date' => now(),
                    'type' => 'reservation',
                ]);

                $reservation->increment('paid_amount', $data['remaining_payment']);

                // Mark compensation as paid if damage was included in this combined payment
                if ($hasDamage && $compensationAmount > 0) {
                    $inspection->update(['compensation_status' => 'paid']);
                }
            }

            // e. Update Reservation
            $reservation->refresh();
            $reservation->update([
                'status' => 'checked_out',
                'actual_check_out' => now(),
            ]);
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
}
