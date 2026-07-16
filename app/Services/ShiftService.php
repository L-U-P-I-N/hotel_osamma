<?php
namespace App\Services;

use App\Models\CashWithdrawal;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Salary;
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

    public function closeShift(Shift $shift, string $notes = '', ?float $actualAmount = null, ?\Carbon\Carbon $endedAt = null): void
    {
        if ($shift->is_closed) {
            throw new \RuntimeException('الوردية مقفلة مسبقاً');
        }

        // وقت انتهاء الوردية: افتراضياً الآن، أو وقت فعلي سابق يحدده المستخدم
        // (لمعالجة الورديات المنسية التي تُقفَل متأخراً)
        $ended = $endedAt ?? now();
        if ($ended->lessThan($shift->started_at)) {
            throw new \RuntimeException('وقت انتهاء الوردية لا يمكن أن يكون قبل وقت بدايتها');
        }
        if ($ended->greaterThan(now()->addMinute())) {
            throw new \RuntimeException('وقت انتهاء الوردية لا يمكن أن يكون في المستقبل');
        }

        $this->computeTotals($shift);
        $shift->refresh();

        $netBalance = $shift->total_received_yer - $shift->total_withdrawals_yer - $shift->total_refunds_yer;
        $shortfall  = $actualAmount !== null ? ($actualAmount - $netBalance) : null;

        // Append this closing event to the history
        $closeEvents   = $shift->close_events ?? [];
        $closeEvents[] = [
            'closed_at'      => now()->toDateTimeString(),
            'ended_at'       => $ended->toDateTimeString(),
            'actual_amount'  => $actualAmount,
            'net_balance'    => $netBalance,
            'shortfall'      => $shortfall,
            'notes'          => $notes ?: null,
            'closed_by'      => auth()->id(),
            'closed_by_name' => auth()->user()?->name,
            'event'          => 'close',
        ];

        $shift->update([
            'is_closed'     => true,
            'closed_at'     => now(),
            'ended_at'      => $ended,
            'notes'         => $notes ?: null,
            'actual_amount' => $actualAmount,
            'shortfall'     => $shortfall,
            'close_events'  => $closeEvents,
        ]);

        AuditLogService::log('update', $shift, ['is_closed' => false], ['is_closed' => true], auth()->user());
    }

    /**
     * إعادة إسناد دفعة إلى وردية أخرى (لمعالجة إيرادات سُجِّلت على الوردية الخطأ
     * بسبب نسيان إقفال وردية سابقة). يُعيد احتساب مجاميع الورديتين.
     */
    public function reassignPayment(Payment $payment, Shift $targetShift, User $actor): void
    {
        $sourceShift = $payment->shift_id ? Shift::find($payment->shift_id) : null;

        if ($sourceShift && $sourceShift->id === $targetShift->id) {
            throw new \RuntimeException('الدفعة موجودة بالفعل في هذه الوردية');
        }

        $old = ['shift_id' => $payment->shift_id];
        $payment->update(['shift_id' => $targetShift->id]);

        // إعادة احتساب مجاميع الورديتين (وفرق الصندوق إن كانتا مقفلتين مع مبلغ فعلي)
        if ($sourceShift) {
            $this->recomputeShiftAfterChange($sourceShift);
        }
        $this->recomputeShiftAfterChange($targetShift);

        AuditLogService::log('update', $payment, $old, ['shift_id' => $targetShift->id], $actor);
    }

    /**
     * نقل سحب (مصروف/صرف عملة) إلى وردية محدَّدة بعينها — يُستخدم عندما يكون
     * السحب منسوباً خطأً لوردية غير التي يخصّها تاريخه فعلياً (مثال: مصروف
     * بتاريخ سابق سُجِّل بينما الوردية المفتوحة يومها كانت وردية لاحقة). إن كان
     * السحب مرتبطاً بمصروف (Expense) نُحدّث نسبته أيضاً حتى يبقى متّسقاً معه.
     */
    public function reassignWithdrawal(CashWithdrawal $withdrawal, Shift $targetShift, User $actor): void
    {
        $sourceShift = $withdrawal->shift_id ? Shift::find($withdrawal->shift_id) : null;

        if ($sourceShift && $sourceShift->id === $targetShift->id) {
            throw new \RuntimeException('السحب موجود بالفعل في هذه الوردية');
        }

        $old = ['shift_id' => $withdrawal->shift_id];
        $withdrawal->update(['shift_id' => $targetShift->id]);

        if ($withdrawal->expense_id) {
            Expense::where('id', $withdrawal->expense_id)->update(['shift_id' => $targetShift->id]);
        }

        if ($sourceShift) {
            $this->recomputeShiftAfterChange($sourceShift);
        }
        $this->recomputeShiftAfterChange($targetShift);

        AuditLogService::log('update', $withdrawal, $old, ['shift_id' => $targetShift->id], $actor);
    }

    /**
     * إنشاء وردية بتاريخ سابق من مجموعة مستلمات محددة ثم إقفالها.
     * تُستخدم عندما تُسجَّل مستلمات تخصّ يوماً سابقاً ضمن الوردية المفتوحة،
     * فتُفصَل هذه المستلمات في وردية مستقلة بتاريخها ويُعاد احتساب الوردية المصدر.
     */
    public function closePastShiftFromPayments(User $user, array $paymentIds, string $shiftDate, ?float $actualAmount, string $notes, User $actor): Shift
    {
        $payments = Payment::whereIn('id', $paymentIds)->get();
        if ($payments->isEmpty()) {
            throw new \RuntimeException('لا توجد مستلمات محددة');
        }

        $date      = \Carbon\Carbon::parse($shiftDate)->startOfDay();
        $startedAt = $payments->min('payment_date') ?: $date->copy();
        $endedAt   = $payments->max('payment_date') ?: $date->copy()->endOfDay();

        $shift = Shift::create([
            'user_id'    => $user->id,
            'shift_date' => $date->toDateString(),
            'started_at' => $startedAt,
            'is_closed'  => false,
        ]);
        AuditLogService::log('create', $shift, null, $shift->toArray(), $actor);

        // نقل المستلمات المحددة إلى الوردية الجديدة وإعادة احتساب الورديات المصدر
        $sourceIds = [];
        foreach ($payments as $p) {
            $old      = ['shift_id' => $p->shift_id];
            $sourceId = $p->shift_id;
            $p->update(['shift_id' => $shift->id]);
            if ($sourceId && $sourceId !== $shift->id) {
                $sourceIds[$sourceId] = true;
            }
            AuditLogService::log('update', $p, $old, ['shift_id' => $shift->id], $actor);
        }
        foreach (array_keys($sourceIds) as $sid) {
            if ($src = Shift::find($sid)) {
                $this->recomputeShiftAfterChange($src);
            }
        }

        // إقفال الوردية الجديدة بوقت انتهاء فعلي = آخر مستلمة
        $this->closeShift($shift, $notes, $actualAmount, \Carbon\Carbon::parse($endedAt));

        return $shift;
    }

    /**
     * إعادة احتساب مجاميع الوردية، ثم إعادة حساب فرق الصندوق (shortfall)
     * إن كانت الوردية مقفلة وسبق إدخال مبلغ فعلي لها — حتى تبقى المطابقة صحيحة
     * بعد نقل الدفعات بين الورديات.
     */
    private function recomputeShiftAfterChange(Shift $shift): void
    {
        $this->computeTotals($shift);
        $shift->refresh();

        if ($shift->is_closed && $shift->actual_amount !== null) {
            $netBalance = $shift->total_received_yer - $shift->total_withdrawals_yer - $shift->total_refunds_yer;
            $shift->update(['shortfall' => (float) $shift->actual_amount - $netBalance]);
        }
    }

    public function reopenShift(Shift $shift, User $requestingUser, string $notes = ''): void
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

        // Append reopen event to history (keep close history intact)
        $closeEvents   = $shift->close_events ?? [];
        $closeEvents[] = [
            'closed_at'      => null,
            'actual_amount'  => null,
            'net_balance'    => null,
            'shortfall'      => null,
            'notes'          => $notes ?: null,
            'closed_by'      => $requestingUser->id,
            'closed_by_name' => $requestingUser->name,
            'event'          => 'reopen',
            'reopened_at'    => now()->toDateTimeString(),
        ];

        $shift->update([
            'is_closed'     => false,
            'closed_at'     => null,
            'ended_at'      => null,
            'actual_amount' => null,
            'shortfall'     => null,
            'close_events'  => $closeEvents,
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

        $refunds = Refund::where('shift_id', $shift->id)->get();
        $rfd = ['YER' => 0, 'SAR' => 0, 'USD' => 0];
        foreach ($refunds as $r) {
            $rfd[$r->currency] = ($rfd[$r->currency] ?? 0) + (float)$r->amount;
        }

        $shift->update([
            'total_received_yer'    => $recv['YER'],
            'total_received_sar'    => $recv['SAR'],
            'total_received_usd'    => $recv['USD'],
            'total_withdrawals_yer' => $wdr['YER'],
            'total_withdrawals_sar' => $wdr['SAR'],
            'total_withdrawals_usd' => $wdr['USD'],
            'total_refunds_yer'     => $rfd['YER'],
            'total_refunds_sar'     => $rfd['SAR'],
            'total_refunds_usd'     => $rfd['USD'],
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

    /**
     * الورديات المقفلة (لأي موظف) القابلة لإعادة الفتح فعلياً — أي أن صاحبها لا
     * يملك وردية مفتوحة حالياً (نفس الشرط الذي تتحقّق منه reopenShift). تُستخدم
     * لعرض قائمة موحّدة للمدير يستطيع منها إعادة فتح وردية أي موظف، بدل الاقتصار
     * على تاريخ ورديات المستخدم الحالي وحده.
     */
    public function getReopenableShifts(int $limit = 40): Collection
    {
        $usersWithOpenShift = Shift::where('is_closed', false)->pluck('user_id')->unique();

        return Shift::with('user')
            ->where('is_closed', true)
            ->whereNotIn('user_id', $usersWithOpenShift)
            ->orderByDesc('shift_date')->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function deductFromSalary(Shift $shift, User $requestingUser): void
    {
        if (!$shift->is_closed) {
            throw new \RuntimeException('لا يمكن خصم من راتب وردية مفتوحة');
        }
        if ($shift->shortfall === null || $shift->shortfall >= 0) {
            throw new \RuntimeException('لا يوجد عجز في هذه الوردية');
        }
        if ($shift->salary_deducted_at !== null) {
            throw new \RuntimeException('تم خصم عجز هذه الوردية من الراتب مسبقاً');
        }

        $shiftUser = User::find($shift->user_id);
        if (!$shiftUser || !$shiftUser->employee_id) {
            throw new \RuntimeException('هذا المستخدم غير مرتبط بسجل موظف في نظام الموارد البشرية');
        }

        $employee = Employee::find($shiftUser->employee_id);
        if (!$employee) {
            throw new \RuntimeException('سجل الموظف غير موجود');
        }

        $deficit = abs((float) $shift->shortfall);
        $month   = $shift->shift_date->month;
        $year    = $shift->shift_date->year;

        $salary = Salary::firstOrCreate(
            ['employee_id' => $employee->id, 'month' => $month, 'year' => $year],
            [
                'base_salary' => $employee->base_salary,
                'bonuses'     => 0,
                'deductions'  => 0,
                'net_salary'  => $employee->base_salary,
                'status'      => 'pending',
                'notes'       => '',
                'created_by'  => $requestingUser->id,
            ]
        );

        $newDeductions = (float) $salary->deductions + $deficit;
        $newNet        = (float) $salary->base_salary + (float) $salary->bonuses - $newDeductions;
        $deductionNote = "خصم عجز وردية #{$shift->id} بتاريخ {$shift->shift_date->format('Y-m-d')}: " . number_format($deficit, 0) . " ر.ي";

        $salary->update([
            'deductions' => $newDeductions,
            'net_salary' => $newNet,
            'notes'      => trim(($salary->notes ? $salary->notes . "\n" : '') . $deductionNote),
        ]);

        $old = $shift->only(['salary_deducted_at', 'salary_deducted_by']);
        $shift->update([
            'salary_deducted_at' => now(),
            'salary_deducted_by' => $requestingUser->id,
        ]);

        AuditLogService::log('update', $shift, $old, $shift->fresh()->only(['salary_deducted_at', 'salary_deducted_by']), $requestingUser);
    }

    public function getAllUsersShiftStatus(): \Illuminate\Support\Collection
    {
        $users = User::with([
            'shifts' => fn($q) => $q->orderBy('id', 'desc')->limit(1),
        ])->get();

        return $users->map(function (User $user) {
            $lastShift = $user->shifts->first();
            return [
                'user'       => $user,
                'lastShift'  => $lastShift,
                'isActive'   => $lastShift && !$lastShift->is_closed,
                'lastDeficit'=> $lastShift?->shortfall,
            ];
        });
    }
}
