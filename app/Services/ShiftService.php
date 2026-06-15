<?php
namespace App\Services;

use App\Models\CashWithdrawal;
use App\Models\Payment;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ShiftService
{
    public function openShift(User $user, string $type): Shift
    {
        $existing = $this->getActiveShift($user);
        if ($existing) {
            throw new \RuntimeException('لديك وردية ' . $existing->type_label . ' مفتوحة بالفعل، أقفلها أولاً');
        }

        $shift = Shift::create([
            'user_id'    => $user->id,
            'shift_date' => today(),
            'shift_type' => $type,
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

    public function closeShift(Shift $shift, string $notes = '', string $empSig = '', string $adminSig = ''): void
    {
        if ($shift->is_closed) {
            throw new \RuntimeException('الوردية مقفلة مسبقاً');
        }

        $this->computeTotals($shift);

        $shift->update([
            'is_closed'          => true,
            'closed_at'          => now(),
            'ended_at'           => now(),
            'notes'              => $notes ?: null,
            'employee_signature' => $empSig  ?: null,
            'admin_signature'    => $adminSig ?: null,
        ]);

        AuditLogService::log('update', $shift, ['is_closed' => false], ['is_closed' => true], auth()->user());
    }

    public function addWithdrawal(Shift $shift, array $data): CashWithdrawal
    {
        if ($shift->is_closed) {
            throw new \RuntimeException('لا يمكن إضافة سحب لوردية مقفلة');
        }

        $withdrawal = CashWithdrawal::create([
            'shift_id'           => $shift->id,
            'cash_settlement_id' => null,
            'amount'             => $data['amount'],
            'currency'           => $data['currency'] ?? 'YER',
            'withdrawal_date'    => $data['withdrawal_date'] ?? now(),
            'withdrawn_by_name'  => $data['withdrawn_by_name'],
            'handed_by_name'     => $data['handed_by_name'] ?? '-',
            'notes'              => $data['notes'] ?? null,
        ]);

        $this->computeTotals($shift);
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
            'total_received_yer'     => $recv['YER'],
            'total_received_sar'     => $recv['SAR'],
            'total_received_usd'     => $recv['USD'],
            'total_withdrawals_yer'  => $wdr['YER'],
            'total_withdrawals_sar'  => $wdr['SAR'],
            'total_withdrawals_usd'  => $wdr['USD'],
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
