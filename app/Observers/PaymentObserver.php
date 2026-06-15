<?php
namespace App\Observers;

use App\Models\Payment;
use App\Services\AuditLogService;
use App\Services\ShiftService;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        AuditLogService::log('create', $payment, null, $payment->toArray());

        if ($payment->shift_id) {
            app(ShiftService::class)->computeTotals($payment->shift);
        }
    }
}
