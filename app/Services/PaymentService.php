<?php
namespace App\Services;

use App\Models\CashSettlement;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private CashSettlementService $settlementService) {}

    public function addPayment(Reservation $reservation, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($reservation, $data, $user) {
            $bankReceiptPath = null;
            if (!empty($data['bank_receipt'])) {
                $bankReceiptPath = $data['bank_receipt']->store('bank_receipts', 'private');
            }

            $payment = Payment::create([
                'reservation_id' => $reservation->id,
                'received_by' => $user->id,
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'YER',
                'method' => $data['method'],
                'bank_receipt_path' => $bankReceiptPath,
                'bank_transfer_ref' => $data['bank_transfer_ref'] ?? null,
                'payment_date' => now(),
                'type' => $data['type'] ?? 'reservation',
                'notes' => $data['notes'] ?? null,
            ]);

            $reservation->increment('paid_amount', $data['amount']);
            $reservation->refresh()->updatePaymentStatus();

            // Update today's settlement if open
            $settlement = CashSettlement::where('user_id', $user->id)
                ->whereDate('shift_date', today())
                ->where('status', 'open')
                ->first();

            if ($settlement) {
                $this->settlementService->computeTotals($settlement);
            }

            AuditLogService::log('create', $payment, null, $payment->toArray(), $user);

            return $payment;
        });
    }
}
