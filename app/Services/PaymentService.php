<?php
namespace App\Services;

use App\Helpers\StorageHelper;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private ShiftService $shiftService) {}

    public function addPayment(Reservation $reservation, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($reservation, $data, $user) {
            $bankReceiptPath = null;
            if (!empty($data['bank_receipt'])) {
                // نستخدم القرص الخاص المُعرَّف (نفس الذي يقرأ منه عارض السند) حتى
                // لا يُخزَّن على قرص ويُبحَث عنه في آخر فيظهر خطأ 404.
                $bankReceiptPath = StorageHelper::store($data['bank_receipt'], 'bank_receipts');
            }

            $shift = $this->shiftService->getActiveShift($user);

            $payment = Payment::create([
                'reservation_id'    => $reservation->id,
                'shift_id'          => $shift?->id,
                'received_by'       => $user->id,
                'amount'            => $data['amount'],
                'currency'          => $data['currency'] ?? 'YER',
                'method'            => $data['method'],
                'bank_receipt_path' => $bankReceiptPath,
                'bank_transfer_ref' => $data['bank_transfer_ref'] ?? null,
                'payment_date'      => now(),
                'type'              => $data['type'] ?? 'reservation',
                'notes'             => $data['notes'] ?? null,
            ]);

            $reservation->increment('paid_amount', $data['amount']);
            $reservation->refresh()->updatePaymentStatus();

            if ($shift) {
                $this->shiftService->computeTotals($shift);
            }

            AuditLogService::log('create', $payment, null, $payment->toArray(), $user);
            return $payment;
        });
    }
}
