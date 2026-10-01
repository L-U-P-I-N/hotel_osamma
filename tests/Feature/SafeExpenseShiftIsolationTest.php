<?php

namespace Tests\Feature;

use App\Models\CashWithdrawal;
use App\Models\Expense;
use App\Models\PaymentAccount;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مصروف الصندوق العام لا يُخصم من درج الوردية.
 *
 * المال خرج من خزنة الفندق لا من يد الموظف، فخصمه من الوردية يُظهر عجزاً في
 * درجٍ لم ينقص — ويُطالَب الموظف بفرقٍ لم يأخذه. يُسجَّل ويظهر في تصدير
 * الوردية موسوماً بأنه من الصندوق العام، ولا يدخل الحساب.
 */
class SafeExpenseShiftIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::role('admin')->firstOrFail();
    }

    private function shift(): Shift
    {
        return Shift::firstOrCreate(
            ['user_id' => $this->admin()->id, 'shift_date' => today(), 'is_closed' => false],
            ['started_at' => now()->subHour(), 'opening_balance_yer' => 0]
        );
    }

    private function safe(): PaymentAccount
    {
        return PaymentAccount::where('type', PaymentAccount::TYPE_SAFE)->firstOrFail();
    }

    private function drawer(): PaymentAccount
    {
        return PaymentAccount::where('type', PaymentAccount::TYPE_SHIFT_CASH)->firstOrFail();
    }

    private function recordExpense(PaymentAccount $from, float $amount): Expense
    {
        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount'             => $amount,
            'category'           => 'other',
            'recipient_name'     => 'مورّد',
            'expense_date'       => today()->toDateString(),
            'payment_method'     => 'cash',
            'payment_account_id' => $from->id,
        ])->assertRedirect();

        return Expense::latest('id')->firstOrFail();
    }

    public function test_a_safe_expense_does_not_touch_the_shift_totals(): void
    {
        $shift = $this->shift();
        $before = (float) $shift->total_withdrawals_yer;

        $expense = $this->recordExpense($this->safe(), 7500);

        $this->assertSame($this->safe()->id, $expense->payment_account_id, 'لم يُحفظ المصروف على الصندوق العام');
        $this->assertSame($before, (float) $shift->fresh()->total_withdrawals_yer, 'خُصم من سحبيات الوردية');
    }

    /** ولا من النقد المتوقَّع في الدرج عند الإقفال */
    public function test_a_safe_expense_does_not_change_the_expected_drawer_cash(): void
    {
        $shift = $this->shift();
        $before = app(\App\Services\ShiftService::class)->expectedDrawerCash($shift);

        $this->recordExpense($this->safe(), 7500);

        $this->assertSame(
            $before,
            app(\App\Services\ShiftService::class)->expectedDrawerCash($shift->fresh()),
            'نقص النقد المتوقَّع في الدرج'
        );
    }

    /** والسحب المرتبط به لا يُنسب للوردية، ويُوسَم بمصدره */
    public function test_the_linked_withdrawal_is_tagged_to_the_safe_and_not_the_shift(): void
    {
        $this->shift();
        $expense = $this->recordExpense($this->safe(), 7500);

        $withdrawal = CashWithdrawal::where('expense_id', $expense->id)->firstOrFail();

        $this->assertNull($withdrawal->shift_id, 'نُسب السحب لوردية');
        $this->assertSame('general_safe', $withdrawal->funding_source);
    }

    /**
     * ولا من حساب صندوق الموظف — وهو ما يُطالَب به عند التسوية.
     * السحب يُربط بالحساب ليظهر في كشفه، لكن مجموعه لا يُحمّل عليه.
     */
    public function test_a_safe_expense_does_not_reduce_the_employee_settlement(): void
    {
        $this->shift();
        $this->recordExpense($this->drawer(), 4000);

        $settlement = \App\Models\CashSettlement::where('user_id', $this->admin()->id)
            ->where('shift_date', today())->firstOrFail();
        $before = (float) $settlement->total_withdrawals;

        $this->recordExpense($this->safe(), 7500);

        $this->assertSame(
            $before,
            (float) $settlement->fresh()->total_withdrawals,
            'حُمّل مصروف الصندوق العام على حساب الموظف'
        );
    }

    /** ومصروف الدرج يبقى على حاله: يُخصم كما كان */
    public function test_a_drawer_expense_is_still_deducted(): void
    {
        $shift = $this->shift();
        $before = (float) $shift->total_withdrawals_yer;

        $this->recordExpense($this->drawer(), 4000);

        $this->assertSame(
            round($before + 4000, 2),
            round((float) $shift->fresh()->total_withdrawals_yer, 2),
            'لم يُخصم مصروف الدرج من الوردية'
        );
    }
}
