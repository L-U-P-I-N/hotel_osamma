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
                'amount'              => $data['amount'],
                'affects_paid_amount' => $adjustPaidAmount,
                'currency'            => $data['currency'] ?? 'YER',
                'method'         => $data['method'],
                'reason'         => $data['reason'],
                'notes'          => $data['notes'] ?? null,
                'refunded_at'    => now(),
            ]);

            // إعادة الحساب تجري دائماً: الاسترجاع الذي لا يُخصم مُعلَّم بذلك في
            // صفّه، فتتجاهله المعادلة من تلقائها بدل أن نتخطى التحديث كلياً.
            $reservation->refresh()->recalculatePaidAmount();

            if ($shift) {
                app(ShiftService::class)->computeTotals($shift);
            }

            // ريال يمني فقط حالياً — عكس قيد الإيراد الأصلي (خروج نقدية)
            if ($refund->currency === 'YER') {
                app(JournalService::class)->post(
                    $refund->refunded_at->toDateString(),
                    'استرجاع لنزيل — حجز #' . $reservation->id,
                    Refund::class,
                    $refund->id,
                    [
                        ['account_code' => '4100', 'debit' => $refund->amount],
                        ['account_code' => '1110', 'credit' => $refund->amount],
                    ],
                    $user->id
                );
            }

            return $refund;
        });
    }
}
