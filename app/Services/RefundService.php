<?php
namespace App\Services;

use App\Models\Refund;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function createRefund(Reservation $reservation, array $data, User $user, bool $adjustPaidAmount = true): Refund
    {
        return DB::transaction(function () use ($reservation, $data, $user, $adjustPaidAmount) {
            $shift = app(ShiftService::class)->getActiveShift($user);

            $refund = Refund::create([
                'reservation_id' => $reservation->id,
                'payment_id'     => $data['payment_id'] ?? null,
                'shift_id'       => $shift?->id,
                'processed_by'   => $user->id,
                'amount'         => $data['amount'],
                'currency'       => $data['currency'] ?? 'YER',
                'method'         => $data['method'],
                'reason'         => $data['reason'],
                'notes'          => $data['notes'] ?? null,
                'refunded_at'    => now(),
            ]);

            if ($adjustPaidAmount) {
                $reservation->decrement('paid_amount', $data['amount']);
                $reservation->refresh()->updatePaymentStatus();
            }

            if ($shift) {
                app(ShiftService::class)->computeTotals($shift);
            }

            return $refund;
        });
    }
}
