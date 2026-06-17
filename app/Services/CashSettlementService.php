<?php
namespace App\Services;

use App\Models\CashSettlement;
use App\Models\CashWithdrawal;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;

class CashSettlementService
{
    public function getOrCreateTodaySettlement(User $user): CashSettlement
    {
        $settlement = CashSettlement::firstOrCreate(
            ['user_id' => $user->id, 'shift_date' => today()],
            ['status' => 'open', 'total_received' => 0, 'total_withdrawals' => 0, 'net_balance' => 0]
        );

        $this->computeTotals($settlement);
        return $settlement->fresh();
    }

    public function addWithdrawal(CashSettlement $settlement, array $data): CashWithdrawal
    {
        if ($settlement->status === 'locked') {
            throw new \RuntimeException('لا يمكن إضافة سحب لحساب مقفل');
        }

        $withdrawal = CashWithdrawal::create([
            'cash_settlement_id' => $settlement->id,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'YER',
            'withdrawal_date' => $data['withdrawal_date'] ?? now(),
            'withdrawn_by_name' => $data['withdrawn_by_name'],
            'handed_by_name' => $data['handed_by_name'],
            'notes' => $data['notes'] ?? null,
        ]);

        $this->computeTotals($settlement);
        return $withdrawal;
    }

    public function saveSignatures(CashSettlement $settlement, string $empSig, string $adminSig): void
    {
        $settlement->update([
            'employee_signature' => $empSig,
            'admin_signature' => $adminSig,
        ]);
    }

    public function lockSettlement(CashSettlement $settlement, User $lockedBy): void
    {
        if ($settlement->status === 'locked') {
            throw new \RuntimeException('الحساب مقفل مسبقاً');
        }

        $settlement->update([
            'status' => 'locked',
            'locked_by' => $lockedBy->id,
            'locked_at' => now(),
        ]);

        AuditLogService::log('update', $settlement, ['status' => 'open'], ['status' => 'locked'], $lockedBy);
    }

    public function computeTotals(CashSettlement $settlement): void
    {
        $totalReceived = Payment::whereHas('reservation', function ($q) use ($settlement) {
            $q->where('created_by', $settlement->user_id);
        })->whereDate('payment_date', $settlement->shift_date)->sum('amount');

        $totalWithdrawals = CashWithdrawal::where('cash_settlement_id', $settlement->id)->sum('amount');

        $settlement->update([
            'total_received' => $totalReceived,
            'total_withdrawals' => $totalWithdrawals,
            'net_balance' => $totalReceived - $totalWithdrawals,
        ]);
    }
}
