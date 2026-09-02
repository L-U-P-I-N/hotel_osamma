<?php
namespace App\Services;

use App\Exceptions\BlacklistedException;
use App\Models\Companion;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CheckInService
{
    public function createCheckIn(array $data, User $user): Reservation
    {
        return DB::transaction(function () use ($data, $user) {

            // 1. فحص القائمة السوداء
            if (!empty($data['id_number'])) {
                $existing = Guest::searchByIdNumber($data['id_number'])->first();
                if ($existing && $existing->is_blacklisted) {
                    throw new BlacklistedException('هذا النزيل موجود في القائمة السوداء: ' . $existing->blacklist_reason);
                }
            }

            // 2. التحقق من الغرفة الرئيسية
            $room = Room::findOrFail($data['room_id']);
            if ($room->status !== 'available') {
                throw new \RuntimeException('الغرفة رقم ' . $room->room_number . ' غير متاحة للحجز');
            }

            // 3. الغرفة المرتبطة (جناح A+B أو شقة)
            $linkedRoom = null;
            $suiteBookingType = null;

            if ($room->isSuiteA() || $room->isSuiteB()) {
                $suiteBookingType = $data['suite_booking_type'] ?? 'a_only';
                if ($suiteBookingType === 'both' && $room->linked_room_id) {
                    $linkedRoom = Room::findOrFail($room->linked_room_id);
                    if ($linkedRoom->status !== 'available') {
                        throw new \RuntimeException('الغرفة المقابلة ' . $linkedRoom->room_number . ' غير متاحة');
                    }
                }
            } elseif ($room->isApartment() && $room->is_always_linked && $room->linked_room_id) {
                $linkedRoom = Room::findOrFail($room->linked_room_id);
                if ($linkedRoom->status !== 'available') {
                    throw new \RuntimeException('الشقة تشمل الغرفة ' . $linkedRoom->room_number . ' وهي غير متاحة');
                }
            }

            // 4. التسعير — يُحسم في الخادم بالكامل: الموظف يرسل سعر الليلة فقط،
            //    ويُتحقق منه مقابل النطاق الذي حدده المدير لنوع الغرفة.
            $nightlyPrice = PricingService::resolveNightlyPrice(
                $room,
                $suiteBookingType,
                $data['nightly_price'] ?? null,
                $user
            );

            $nights = max(1, Carbon::parse($data['check_in_date'])
                ->diffInDays(Carbon::parse($data['check_out_date'])));
            $totalAmount = round($nightlyPrice * $nights, 2);

            // 5. صورة الهوية
            $idImagePath = null;
            if (!empty($data['id_image'])) {
                $idImagePath = $data['id_image']->store('id_images/guests', 'private');
            }

            // 6. النزيل
            $guest = Guest::firstOrNew(['id_number' => $data['id_number']]);
            $guest->fill([
                'full_name'     => $data['full_name'],
                'nationality'   => $data['nationality'] ?? null,
                'occupation'    => $data['occupation'] ?? null,
                'id_type'       => $data['id_type'],
                'id_number'     => $data['id_number'],
                'id_issuer'     => $data['id_issuer'] ?? null,
                'id_issue_date' => $data['id_issue_date'] ?? null,
                'phone'         => $data['phone'] ?? null,
                'id_image_path' => $idImagePath ?? $guest->id_image_path,
            ]);
            $guest->save();

            // 7. الحجز
            $reservation = Reservation::create([
                'guest_id'           => $guest->id,
                'room_id'            => $room->id,
                'linked_room_id'     => $linkedRoom?->id,
                'suite_booking_type' => $suiteBookingType,
                'created_by'         => $user->id,
                'check_in_date'      => $data['check_in_date'],
                'check_out_date'     => $data['check_out_date'],
                'origin'             => $data['origin'] ?? null,
                'purpose'            => $data['purpose'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'status'             => 'checked_in',
                'payment_status'     => $data['payment_status'],
                'nightly_price'      => $nightlyPrice,
                'total_amount'       => $totalAmount,
                'paid_amount'        => 0,
                'admin_approval_id'  => $data['payment_status'] === 'deferred'
                                            ? ($data['admin_approval_id'] ?? null) : null,
            ]);

            // 8. تحديث حالة الغرف
            $room->update(['status' => 'occupied']);
            if ($linkedRoom) {
                $linkedRoom->update(['status' => 'occupied']);
            }

            // 9. المرافقون
            if (!empty($data['companions'])) {
                foreach ($data['companions'] as $companionData) {
                    $compImgPath = null;
                    if (!empty($companionData['id_image'])) {
                        $compImgPath = $companionData['id_image']->store('id_images/companions', 'private');
                    }
                    $marriageDocPath = null;
                    if (($companionData['relationship'] ?? '') === 'wife' && !empty($companionData['marriage_doc'])) {
                        $marriageDocPath = $companionData['marriage_doc']->store('marriage_docs', 'private');
                    }
                    Companion::create([
                        'reservation_id' => $reservation->id,
                        'full_name'      => $companionData['full_name'],
                        'nationality'    => $companionData['nationality'] ?? null,
                        'id_type'        => $companionData['id_type'] ?? 'national_id',
                        'id_number'      => $companionData['id_number'] ?? null,
                        'id_issuer'      => $companionData['id_issuer'] ?? null,
                        'id_issue_date'  => $companionData['id_issue_date'] ?? null,
                        'relationship'   => $companionData['relationship'],
                        'id_image_path'  => $compImgPath,
                        'marriage_doc_path' => $marriageDocPath,
                    ]);
                }
            }

            // 10. الدفعة الأولى — ربطها بالوردية المفتوحة
            $paidAmount = round((float) ($data['paid_amount'] ?? 0), 2);
            // الدفعة لا تتجاوز الإجمالي المحسوب في الخادم مهما أرسل المتصفح
            $paidAmount = min($paidAmount, $totalAmount);

            if ($paidAmount > 0) {
                $bankReceiptPath = null;
                if (!empty($data['bank_receipt'])) {
                    $bankReceiptPath = $data['bank_receipt']->store('bank_receipts', 'private');
                }

                $shift = app(ShiftService::class)->getActiveShift($user);

                Payment::create([
                    'reservation_id'    => $reservation->id,
                    'shift_id'          => $shift?->id,
                    'received_by'       => $user->id,
                    'amount'            => $paidAmount,
                    'currency'          => $data['currency'] ?? 'YER',
                    'method'            => $data['payment_method'] ?? 'cash',
                    'bank_receipt_path' => $bankReceiptPath,
                    'bank_transfer_ref' => $data['bank_transfer_ref'] ?? null,
                    'payment_date'      => now(),
                    'type'              => 'reservation',
                ]);

                $reservation->increment('paid_amount', $paidAmount);
                $reservation->updatePaymentStatus();

                if ($shift) {
                    app(ShiftService::class)->computeTotals($shift);
                }
            }

            AuditLogService::log('create', $reservation, null, $reservation->toArray(), $user);
            return $reservation->fresh();
        });
    }
}
