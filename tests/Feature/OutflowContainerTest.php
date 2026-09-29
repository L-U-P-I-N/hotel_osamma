<?php
namespace Tests\Feature;

use App\Models\CashWithdrawal;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\PaymentAccount;
use App\Models\Salary;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ما يخرج من المال يخرج من وعاء بعينه.
 *
 * كان المصروف يُقيَّد خصماً من درج الوردية دائماً، والمصروف المدفوع بتحويل بنكي
 * يُقيَّد ذمةً دائنة (والمال غادر البنك فعلاً)، والراتب مثبَّتاً على الصندوق العام.
 * فلم يكن ممكناً أن يُدفع شيء من حساب بنكي ويظهر خصماً منه.
 */
class OutflowContainerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function shift(): Shift
    {
        return Shift::firstOrCreate(
            ['user_id' => $this->admin()->id, 'shift_date' => today(), 'is_closed' => false],
            ['started_at' => now()->subHour(), 'opening_balance_yer' => 0]
        );
    }

    private function bank(): PaymentAccount { return PaymentAccount::where('type', 'bank')->firstOrFail(); }
    private function safe(): PaymentAccount { return PaymentAccount::where('type', 'safe')->firstOrFail(); }
    private function drawer(): PaymentAccount { return PaymentAccount::where('type', 'shift_cash')->firstOrFail(); }

    private function entryFor(string $type, int $id, string $event = 'legacy'): JournalEntry
    {
        return JournalEntry::where('source_type', $type)->where('source_id', $id)
            ->where('event', $event)->firstOrFail();
    }

    /* ═══ المصروفات ═══ */

    public function test_a_cash_expense_leaves_the_chosen_cash_box(): void
    {
        $this->shift();

        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 25000, 'category' => 'cleaning', 'recipient_name' => 'مورّد نظافة',
            'expense_date' => today()->toDateString(),
            'payment_method' => 'cash', 'payment_account_id' => $this->safe()->id,
        ])->assertRedirect();

        $expense = Expense::where('recipient_name', 'مورّد نظافة')->firstOrFail();

        $this->assertSame($this->safe()->id, $expense->payment_account_id);

        $entry = $this->entryFor(Expense::class, $expense->id);
        $this->assertEqualsWithDelta(25000, $entry->lines()->where('account_code', '5140')->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(25000, $entry->lines()->where('account_code', '1120')->sum('credit'), 0.01);
    }

    /**
     * مصروف بتحويل بنكي يُنقص البنك. كان يُقيَّد ذمةً دائنة (2150) رغم أن المال
     * غادر البنك وقتها، فيبقى رصيد البنك مرتفعاً وتتضخّم الذمم بلا مقابل.
     */
    public function test_a_bank_transfer_expense_reduces_the_bank_not_the_payables(): void
    {
        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 80000, 'category' => 'maintenance', 'recipient_name' => 'شركة المصاعد',
            'expense_date' => today()->toDateString(),
            'payment_method' => 'bank_transfer', 'payment_account_id' => $this->bank()->id,
        ])->assertRedirect();

        $expense = Expense::where('recipient_name', 'شركة المصاعد')->firstOrFail();
        $entry   = $this->entryFor(Expense::class, $expense->id);

        $this->assertEqualsWithDelta(80000, $entry->lines()->where('account_code', '6330')->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(80000, $entry->lines()->where('account_code', '1131')->sum('credit'), 0.01);
        $this->assertSame(0, $entry->lines()->where('account_code', '2150')->count(), 'قُيّد التحويل ذمةً دائنة');
        $this->assertEqualsWithDelta(-80000, $this->bank()->balance, 0.01);
    }

    /** و«لاحقاً» وحده يبقى ذمةً دائنة — المال لم يخرج بعد. */
    public function test_a_deferred_expense_stays_a_payable_with_no_container(): void
    {
        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 40000, 'category' => 'other', 'recipient_name' => 'مورّد آجل',
            'expense_date' => today()->toDateString(), 'payment_method' => 'later',
        ])->assertRedirect();

        $expense = Expense::where('recipient_name', 'مورّد آجل')->firstOrFail();

        $this->assertNull($expense->payment_account_id);
        $this->assertEqualsWithDelta(
            40000,
            $this->entryFor(Expense::class, $expense->id)->lines()->where('account_code', '2150')->sum('credit'),
            0.01
        );
    }

    /** ووعاء لا يناسب الطريقة يُستبدل بالافتراضي — لا مصروف نقدي من حساب بنكي. */
    public function test_a_mismatched_container_is_replaced(): void
    {
        $this->shift();

        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 5000, 'category' => 'food', 'recipient_name' => 'بقالة',
            'expense_date' => today()->toDateString(),
            'payment_method' => 'cash', 'payment_account_id' => $this->bank()->id,
        ])->assertRedirect();

        $expense = Expense::where('recipient_name', 'بقالة')->firstOrFail();

        $this->assertNotSame($this->bank()->id, $expense->payment_account_id);
        $this->assertTrue($expense->paymentAccount->is_cash);
    }

    /* ═══ السحبيات ═══ */

    public function test_a_withdrawal_leaves_the_chosen_cash_box(): void
    {
        $shift = $this->shift();

        $this->actingAs($this->admin())->post(route('shifts.withdrawal'), [
            'amount' => 12000, 'currency' => 'YER', 'withdrawn_by_name' => 'أحمد',
            'withdrawal_type' => 'expense', 'category' => 'other',
            'funding_source' => 'shift', 'payment_account_id' => $this->drawer()->id,
        ])->assertRedirect();

        $withdrawal = CashWithdrawal::where('withdrawn_by_name', 'أحمد')->firstOrFail();

        $this->assertSame($this->drawer()->id, $withdrawal->payment_account_id);
        $this->assertEqualsWithDelta(
            12000,
            $this->entryFor(CashWithdrawal::class, $withdrawal->id)->lines()->where('account_code', '1111')->sum('credit'),
            0.01
        );
        unset($shift);
    }

    /** وسحبية الصندوق العام تُنقص الخزنة لا الدرج. */
    public function test_a_general_safe_withdrawal_leaves_the_safe(): void
    {
        $this->actingAs($this->admin())->post(route('shifts.withdrawal'), [
            'amount' => 9000, 'currency' => 'YER', 'withdrawn_by_name' => 'سالم',
            'withdrawal_type' => 'expense', 'category' => 'other', 'funding_source' => 'general_safe',
        ])->assertRedirect();

        $withdrawal = CashWithdrawal::where('withdrawn_by_name', 'سالم')->firstOrFail();

        $this->assertSame($this->safe()->id, $withdrawal->payment_account_id);
        $this->assertEqualsWithDelta(
            9000,
            $this->entryFor(CashWithdrawal::class, $withdrawal->id)->lines()->where('account_code', '1120')->sum('credit'),
            0.01
        );
    }

    /* ═══ مسار واحد للصرف ═══ */

    /**
     * مصروف من الخزنة لا يُنقص درج الوردية.
     *
     * سجل السحب المرافق للمصروف كان يُربط بالوردية دائماً، فمصروفٌ دُفع من
     * الخزنة كان يُنقص المتوقَّع في درج الموظف ويُظهره عاجزاً بمبلغ لم يخرج
     * من يده — نفس عيب التحويل البنكي الذي عولج قبله.
     */
    public function test_a_safe_expense_does_not_reduce_the_shift_drawer(): void
    {
        $shift = $this->shift();

        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 50000, 'category' => 'other', 'recipient_name' => 'من الخزنة',
            'expense_date' => today()->toDateString(),
            'payment_method' => 'cash', 'payment_account_id' => $this->safe()->id,
        ])->assertRedirect();

        $withdrawal = CashWithdrawal::whereHas('expense', fn ($q) => $q->where('recipient_name', 'من الخزنة'))->first();

        $this->assertNotNull($withdrawal, 'لم يُنشأ سجل السحب المرافق');
        $this->assertNull($withdrawal->shift_id, 'رُبط مصروف الخزنة بالوردية');
        $this->assertSame('general_safe', $withdrawal->funding_source);
        $this->assertSame($this->safe()->id, $withdrawal->payment_account_id);

        app(\App\Services\ShiftService::class)->computeTotals($shift);
        $this->assertEqualsWithDelta(0, $shift->refresh()->total_withdrawals_yer, 0.01);
    }

    /** ومصروف من الدرج يُنقصه كما يجب. */
    public function test_a_drawer_expense_does_reduce_the_shift_drawer(): void
    {
        $shift = $this->shift();

        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 7000, 'category' => 'other', 'recipient_name' => 'من الدرج',
            'expense_date' => today()->toDateString(),
            'payment_method' => 'cash', 'payment_account_id' => $this->drawer()->id,
        ])->assertRedirect();

        app(\App\Services\ShiftService::class)->computeTotals($shift);

        $this->assertEqualsWithDelta(7000, $shift->refresh()->total_withdrawals_yer, 0.01);
    }

    /** ومصروف بتحويل بنكي لا يُنشئ سحباً نقدياً أصلاً. */
    public function test_a_bank_expense_creates_no_cash_withdrawal(): void
    {
        $shift = $this->shift();

        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 33000, 'category' => 'other', 'recipient_name' => 'بتحويل',
            'expense_date' => today()->toDateString(),
            'payment_method' => 'bank_transfer', 'payment_account_id' => $this->bank()->id,
        ])->assertRedirect();

        $expense = Expense::where('recipient_name', 'بتحويل')->firstOrFail();

        $this->assertSame(0, CashWithdrawal::where('expense_id', $expense->id)->count());
        app(\App\Services\ShiftService::class)->computeTotals($shift);
        $this->assertEqualsWithDelta(0, $shift->refresh()->total_withdrawals_yer, 0.01);
    }

    /** صفحة المصروفات لم يبقَ فيها نموذج سحبٍ موازٍ. */
    public function test_the_expenses_page_no_longer_offers_a_second_form(): void
    {
        $html = $this->actingAs($this->admin())->get(route('expenses.index'))->assertOk()->getContent();

        $this->assertStringNotContainsString('تسجيل سحب', $html);
        $this->assertStringNotContainsString('name="funding_source"', $html);
        $this->assertStringContainsString('تسجيل مصروف', $html);
    }

    /** وصفحة الصناديق تفتح نموذج المصروف جاهزاً على صندوقها. */
    public function test_the_containers_screen_links_into_the_single_expense_form(): void
    {
        $safe = $this->safe();

        $this->actingAs($this->admin())->get(route('payment-accounts.index'))
            ->assertOk()
            ->assertSee('صرف مصروف من هذا الصندوق', false)
            ->assertSee('payment_account_id=' . $safe->id, false);

        $this->actingAs($this->admin())
            ->get(route('expenses.create', ['payment_account_id' => $safe->id, 'payment_method' => 'cash']))
            ->assertOk()
            ->assertSee('value="' . $safe->id . '" selected', false);
    }

    /* ═══ الرواتب ═══ */

    private function salary(): Salary
    {
        $employee = Employee::create([
            'name' => 'موظف الراتب', 'national_id' => '88' . random_int(10000000, 99999999),
            'position' => 'استقبال', 'base_salary' => 90000, 'hire_date' => today()->subYear(),
        ]);

        return Salary::create([
            'employee_id' => $employee->id,
            'month' => (int) today()->format('m'), 'year' => (int) today()->format('Y'),
            'base_salary' => 90000, 'net_salary' => 90000,
            'status' => 'pending', 'created_by' => $this->admin()->id,
        ]);
    }

    public function test_a_salary_paid_by_bank_transfer_reduces_the_bank(): void
    {
        $salary = $this->salary();

        $this->actingAs($this->admin())->patch(route('salaries.markPaid', $salary), [
            'payment_method' => 'bank_transfer', 'payment_account_id' => $this->bank()->id,
        ])->assertRedirect();

        $salary->refresh();
        $this->assertSame('paid', $salary->status);
        $this->assertSame($this->bank()->id, $salary->payment_account_id);

        $paid = $this->entryFor(Salary::class, $salary->id, 'payroll.paid');
        $this->assertEqualsWithDelta(90000, $paid->lines()->where('account_code', '1131')->sum('credit'), 0.01);
        $this->assertSame(0, $paid->lines()->where('account_code', '1120')->count(), 'خُصم الراتب من الخزنة رغم تحويله بنكياً');
    }

    /** والاستحقاق يُرحَّل مع الصرف، فيتراصد حساب الرواتب المستحقة. */
    public function test_paying_a_salary_posts_both_accrual_and_payment(): void
    {
        $salary = $this->salary();

        $this->actingAs($this->admin())->patch(route('salaries.markPaid', $salary), [
            'payment_method' => 'cash', 'payment_account_id' => $this->safe()->id,
        ])->assertRedirect();

        $accrual = $this->entryFor(Salary::class, $salary->id, 'payroll.accrued');
        $this->assertEqualsWithDelta(90000, $accrual->lines()->where('account_code', '6113')->sum('debit'), 0.01);

        $this->assertEqualsWithDelta(
            0,
            \App\Models\ChartOfAccount::where('code', '2410')->first()->balance,
            0.01,
            'حساب الرواتب المستحقة لم يتراصد بعد الاستحقاق والصرف'
        );
        $this->assertEqualsWithDelta(-90000, $this->safe()->balance, 0.01);
    }

    /** وتكرار الضغط على «مدفوعة» لا يُرحّل الراتب مرتين. */
    public function test_marking_a_salary_paid_twice_posts_once(): void
    {
        $salary = $this->salary();

        foreach ([1, 2] as $ignored) {
            $this->actingAs($this->admin())->patch(route('salaries.markPaid', $salary), [
                'payment_method' => 'cash', 'payment_account_id' => $this->safe()->id,
            ]);
        }

        $this->assertSame(2, JournalEntry::where('source_type', Salary::class)->where('source_id', $salary->id)->count());
        $this->assertEqualsWithDelta(-90000, $this->safe()->balance, 0.01);
    }
}
