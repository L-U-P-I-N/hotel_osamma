<?php
namespace App\Services;

use App\Models\CashWithdrawal;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ShiftService
{
    public function openShift(User $user): Shift
    {
        // إذا كان لديه وردية مفتوحة بالفعل، أعدها (لا تفتح جديدة)
        $existing = $this->getActiveShift($user);
        if ($existing) {
            return $existing;
        }

        $shift = Shift::create([
            'user_id'    => $user->id,
            'shift_date' => today(),
            'started_at' => now(),
            'is_closed'  => false,
        ]);

        AuditLogService::log('create', $shift, null, $shift->toArray(), $user);
        return $shift;
    }

    public function getActiveShift(User $user): ?Shift
    {
        return Shift::where('user_id', $user->id)
            ->where('is_closed', false)
            ->latest()
            ->first();
    }

    public function closeShift(Shift $shift, string $notes = '', ?float $actualAmount = null): void
    {
        if ($shift->is_closed) {
            throw new \RuntimeException('الوردية مقفلة مسبقاً');
        }

        $this->computeTotals($shift);
        $shift->refresh();

        $netBalance = $shift->total_received_yer - $shift->total_withdrawals_yer;

        $shift->update([
            'is_closed'     => true,
            'closed_at'     => now(),
            'ended_at'      => now(),
            'notes'         => $notes ?: null,
            'actual_amount' => $actualAmount,
            'shortfall'     => $actualAmount !== null ? ($actualAmount - $netBalance) : null,
        ]);

        AuditLogService::log('update', $shift, ['is_closed' => false], ['is_closed' => true], auth()->user());
    }

    public function reopenShift(Shift $shift, User $requestingUser): void
    {
        if (!$shift->is_closed) {
            throw new \RuntimeException('الوردية مفتوحة بالفعل');
        }

        // لا يمكن فتح وردية بينما الموظف لديه وردية أخرى مفتوحة
        $shiftOwner = User::find($shift->user_id);
        if ($shiftOwner && $this->getActiveShift($shiftOwner)) {
            throw new \RuntimeException('لا يمكن فتح الإقفال لأن الموظف لديه وردية مفتوحة حالياً');
        }

        $old = $shift->only(['is_closed', 'closed_at', 'ended_at', 'actual_amount', 'shortfall']);

        $shift->update([
            'is_closed'     => false,
            'closed_at'     => null,
            'ended_at'      => null,
            'actual_amount' => null,
            'shortfall'     => null,
        ]);

        AuditLogService::log('update', $shift, $old, $shift->fresh()->toArray(), $requestingUser);
    }

    public function addWithdrawal(Shift $shift, array $data): CashWithdrawal
    {
        if ($shift->is_closed) {
            throw new \RuntimeException('لا يمكن إضافة سحب لوردية مقفلة');
        }

        $type = $data['withdrawal_type'] ?? 'expense';

        // إنشاء سجل مصروف تلقائياً لكل سحب من نوع "مصروف"
        $expenseId = null;
        if ($type === 'expense') {
            $expense = Expense::create([
                'amount'         => $data['amount'],
                'currency'       => $data['currency'] ?? 'YER',
                'category'       => $data['category'] ?? 'other',
                'recipient_name' => $data['withdrawn_by_name'],
                'description'    => $data['notes'] ?? null,
                'expense_date'   => now()->toDateString(),
                'paid_by'        => auth()->id(),
                'shift_id'       => $shift->id,
                'payment_method' => 'cash',
            ]);
            $expenseId = $expense->id;
        }

        $withdrawal = CashWithdrawal::create([
            'shift_id'             => $shift->id,
            'cash_settlement_id'   => null,
            'expense_id'           => $expenseId,
            'amount'               => $data['amount'],
            'currency'             => $data['currency'] ?? 'YER',
            'withdrawal_date'      => $data['withdrawal_date'] ?? now(),
            'withdrawn_by_name'    => $data['withdrawn_by_name'],
            'handed_by_name'       => $data['handed_by_name'] ?? '-',
            'notes'                => $data['notes'] ?? null,
            'withdrawal_type'      => $type,
            'exchange_to_currency' => $type === 'currency_exchange' ? ($data['exchange_to_currency'] ?? null) : null,
            'exchange_to_amount'   => $type === 'currency_exchange' ? ($data['exchange_to_amount'] ?? null) : null,
        ]);

        return $withdrawal;
    }

    public function linkPaymentToShift(Payment $payment): void
    {
        $user = User::find($payment->received_by);
        if (!$user) return;

        $shift = $this->getActiveShift($user);
        if ($shift) {
            $payment->update(['shift_id' => $shift->id]);
            $this->computeTotals($shift);
        }
    }

    public function computeTotals(Shift $shift): void
    {
        $payments = Payment::where('shift_id', $shift->id)->get();
        $recv = ['YER' => 0, 'SAR' => 0, 'USD' => 0];
        foreach ($payments as $p) {
            $recv[$p->currency] = ($recv[$p->currency] ?? 0) + (float)$p->amount;
        }

        $withdrawals = CashWithdrawal::where('shift_id', $shift->id)->get();
        $wdr = ['YER' => 0, 'SAR' => 0, 'USD' => 0];
        foreach ($withdrawals as $w) {
            $wdr[$w->currency] = ($wdr[$w->currency] ?? 0) + (float)$w->amount;
        }

        $shift->update([
            'total_received_yer'    => $recv['YER'],
            'total_received_sar'    => $recv['SAR'],
            'total_received_usd'    => $recv['USD'],
            'total_withdrawals_yer' => $wdr['YER'],
            'total_withdrawals_sar' => $wdr['SAR'],
            'total_withdrawals_usd' => $wdr['USD'],
        ]);
    }

    public function getHistory(User $user, int $limit = 10): Collection
    {
        return Shift::where('user_id', $user->id)
            ->with(['withdrawals'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function getAllActiveShifts(): Collection
    {
        return Shift::where('is_closed', false)
            ->with(['user', 'payments', 'withdrawals'])
            ->latest()
            ->get();
    }
}
