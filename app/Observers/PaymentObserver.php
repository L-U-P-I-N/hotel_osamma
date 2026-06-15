<?php
namespace App\Observers;

use App\Models\CashSettlement;
use App\Models\Payment;
use App\Services\AuditLogService;
use App\Services\CashSettlementService;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        AuditLogService::log('create', $payment, null, $payment->toArray());

        $settlement = CashSettlement::where('user_id', $payment->received_by)
            ->whereDate('shift_date', $payment->payment_date->toDateString())
            ->where('status', 'open')
            ->first();

        if ($settlement) {
            app(CashSettlementService::class)->computeTotals($settlement);
        }
    }
}
