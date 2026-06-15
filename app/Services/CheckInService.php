<?php
namespace App\Services;

use App\Models\Companion;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Exceptions\BlacklistedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CheckInService
{
    public function createCheckIn(array $data, User $user): Reservation
    {
        return DB::transaction(function () use ($data, $user) {
            // a. Check blacklist
            if (isset($data['id_number'])) {
                $existing = Guest::searchByIdNumber($data['id_number'])->first();
                if ($existing && $existing->is_blacklisted) {
                    throw new BlacklistedException('هذا النزيل موجود في القائمة السوداء: ' . $existing->blacklist_reason);
                }
            }

            // b. Validate room is available
            $room = \App\Models\Room::findOrFail($data['room_id']);
            if ($room->status !== 'available') {
                throw new \RuntimeException('الغرفة غير متاحة للحجز');
            }

            // c. Store id image
            $idImagePath = null;
            if (!empty($data['id_image'])) {
                $idImagePath = $data['id_image']->store('id_images/guests', 'private');
            }

            // Create/update Guest
            $guestData = [
                'full_name' => $data['full_name'],
                'nationality' => $data['nationality'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'id_type' => $data['id_type'],
                'id_number' => $data['id_number'],
                'id_issuer' => $data['id_issuer'] ?? null,
                'id_issue_date' => $data['id_issue_date'] ?? null,
                'phone' => $data['phone'] ?? null,
                'id_image_path' => $idImagePath,
            ];

            $guest = Guest::firstOrNew(['id_number' => $data['id_number']]);
            $guest->fill($guestData);
            $guest->save();

            // d. Create Reservation
            $reservation = Reservation::create([
                'guest_id' => $guest->id,
                'room_id' => $room->id,
                'created_by' => $user->id,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'origin' => $data['origin'] ?? null,
                'purpose' => $data['purpose'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'checked_in',
                'payment_status' => $data['payment_status'],
                'total_amount' => $data['total_amount'],
                'paid_amount' => 0,
                'admin_approval_id' => $data['payment_status'] === 'deferred' ? ($data['admin_approval_id'] ?? null) : null,
            ]);

            // Update room status
            $room->update(['status' => 'occupied']);

            // e. Save Companions
            if (!empty($data['companions'])) {
                foreach ($data['companions'] as $index => $companionData) {
                    $compIdImagePath = null;
                    if (!empty($companionData['id_image'])) {
                        $compIdImagePath = $companionData['id_image']->store('id_images/companions', 'private');
                    }

                    $marriageDocPath = null;
                    if ($companionData['relationship'] === 'wife' && !empty($companionData['marriage_doc'])) {
                        $marriageDocPath = $companionData['marriage_doc']->store('marriage_docs', 'private');
                    }

                    Companion::create([
                        'reservation_id' => $reservation->id,
                        'full_name' => $companionData['full_name'],
                        'nationality' => $companionData['nationality'] ?? null,
                        'id_type' => $companionData['id_type'] ?? 'national_id',
                        'id_number' => $companionData['id_number'] ?? null,
                        'id_issuer' => $companionData['id_issuer'] ?? null,
                        'id_issue_date' => $companionData['id_issue_date'] ?? null,
                        'relationship' => $companionData['relationship'],
                        'id_image_path' => $compIdImagePath,
                        'marriage_doc_path' => $marriageDocPath,
                    ]);
                }
            }

            // f. Handle deferred + log
            if ($data['payment_status'] === 'deferred') {
                AuditLogService::log('create', $reservation, null, [
                    'note' => 'حجز مؤجل - يتطلب موافقة الإدارة',
                    'admin_approval_id' => $data['admin_approval_id'] ?? null,
                ], $user);
            }

            // g. Create Payment if amount > 0
            if (!empty($data['paid_amount']) && $data['paid_amount'] > 0) {
                $bankReceiptPath = null;
                if (!empty($data['bank_receipt'])) {
                    $bankReceiptPath = $data['bank_receipt']->store('bank_receipts', 'private');
                }

                Payment::create([
                    'reservation_id' => $reservation->id,
                    'received_by' => $user->id,
                    'amount' => $data['paid_amount'],
                    'currency' => $data['currency'] ?? 'YER',
                    'method' => $data['payment_method'] ?? 'cash',
                    'bank_receipt_path' => $bankReceiptPath,
                    'bank_transfer_ref' => $data['bank_transfer_ref'] ?? null,
                    'payment_date' => now(),
                    'type' => 'reservation',
                    'notes' => null,
                ]);

                $reservation->increment('paid_amount', $data['paid_amount']);
                $reservation->updatePaymentStatus();
            }

            // h. Log audit
            AuditLogService::log('create', $reservation, null, $reservation->toArray(), $user);

            return $reservation->fresh();
        });
    }
}
