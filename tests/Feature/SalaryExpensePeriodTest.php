<?php
namespace Tests\Feature;

use App\Models\CashWithdrawal;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\PaymentAccount;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * راتبٌ يُصرف من شاشة المصروفات يخصّ شهره هو، والصرف من الخزنة لا يُعجِز الدرج.
 *
 * بلاغان في حادثة واحدة: راتب سبتمبر صُرف في أكتوبر فخُصم من راتب أكتوبر،
 * وصُرف «من الخزنة» فظهر عجزاً في وردية الموظف.
 */
class SalaryExpensePeriodTest extends TestCase
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

    private function safe(): PaymentAccount   { return PaymentAccount::where('type', 'safe')->firstOrFail(); }
    private function drawer(): PaymentAccount { return PaymentAccount::where('type', 'shift_cash')->firstOrFail(); }
    private function bank(): PaymentAccount   { return PaymentAccount::where('type', 'bank')->firstOrFail(); }

    private function employee(): Employee
    {
        return Employee::firstOrCreate(
            ['name' => 'محمد الاستقبال'],
            ['position' => 'موظف استقبال', 'base_salary' => 100000, 'is_active' => true, 'hire_date' => now()->subYear()]
        );
    }

    /* ═══ شهر الراتب ═══ */

    /** راتب سبتمبر المصروف في أكتوبر يُخصم من سبتمبر، لا من الشهر الجاري. */
    public function test_a_salary_expense_is_charged_to_the_month_it_is_for(): void
    {
        $employee = $this->employee();
        $this->shift();

        $paidOn = \Carbon\Carbon::create(2025, 10, 12);

        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 80000, 'category' => 'salary', 'recipient_name' => $employee->name,
            'employee_id' => $employee->id,
            'salary_month' => 9, 'salary_year' => 2025,
            'expense_date' => $paidOn->toDateString(),
            'payment_method' => 'cash', 'payment_account_id' => $this->drawer()->id,
        ])->assertRedirect(route('expenses.index'));

        $expense = Expense::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame(9, $expense->salary_month);
        $this->assertSame(2025, $expense->salary_year);

        // الخصم يقع على سبتمبر وحده
        $this->assertEqualsWithDelta(80000, $employee->expensesTotalForMonth(9, 2025), 0.01);
        $this->assertEqualsWithDelta(0, $employee->expensesTotalForMonth(10, 2025), 0.01);
        $this->assertEqualsWithDelta(80000, $employee->withdrawalsTotalForMonth(9, 2025), 0.01);
    }

    /** وفئة الرواتب لا تُسجَّل بلا شهر — وإلا عاد التخمين من التاريخ. */
    public function test_a_salary_expense_without_a_month_is_refused(): void
    {
        $this->shift();

        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 50000, 'category' => 'salary', 'recipient_name' => 'موظف',
            'expense_date' => today()->toDateString(), 'payment_method' => 'cash',
        ])->assertSessionHasErrors('salary_month');

        $this->assertSame(0, Expense::where('recipient_name', 'موظف')->count());
    }

    /** وغير الرواتب يبقى على شهر تاريخه كما كان. */
    public function test_a_non_salary_expense_still_follows_its_date(): void
    {
        $employee = $this->employee();
        $this->shift();

        $when = \Carbon\Carbon::create(2025, 10, 5);

        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 3000, 'category' => 'other', 'recipient_name' => $employee->name,
            'employee_id' => $employee->id, 'salary_month' => 9, 'salary_year' => 2025,
            'expense_date' => $when->toDateString(),
            'payment_method' => 'cash', 'payment_account_id' => $this->drawer()->id,
        ])->assertRedirect();

        $expense = Expense::where('employee_id', $employee->id)->firstOrFail();
        $this->assertNull($expense->salary_month, 'شهر الراتب حُفظ في غير فئة الرواتب');
        $this->assertEqualsWithDelta(3000, $employee->expensesTotalForMonth(10, 2025), 0.01);
        $this->assertEqualsWithDelta(0, $employee->expensesTotalForMonth(9, 2025), 0.01);
    }

    /** وتصحيح الفئة من «رواتب» إلى غيرها يمحو الشهر فلا يبقى الخصم معلَّقاً عليه. */
    public function test_changing_the_category_away_from_salary_clears_the_period(): void
    {
        $employee = $this->employee();
        $this->shift();

        $expense = Expense::create([
            'amount' => 20000, 'currency' => 'YER', 'category' => 'salary',
            'recipient_name' => $employee->name, 'employee_id' => $employee->id,
            'salary_month' => 9, 'salary_year' => 2025,
            'expense_date' => '2025-10-12', 'paid_by' => $this->admin()->id,
            'payment_method' => 'cash', 'payment_account_id' => $this->drawer()->id,
        ]);

        $this->actingAs($this->admin())->put(route('expenses.update', $expense), [
            'amount' => 20000, 'category' => 'other', 'recipient_name' => $employee->name,
            'employee_id' => $employee->id, 'expense_date' => '2025-10-12',
            'payment_method' => 'cash', 'payment_account_id' => $this->drawer()->id,
        ])->assertRedirect();

        $this->assertNull($expense->refresh()->salary_month);
    }

    /* ═══ الصرف من الخزنة ═══ */

    /**
     * وعاءٌ لا يناسب الطريقة يُرفض بدل أن يُستبدل صامتاً.
     *
     * هذا ما أخفى البلاغ: حقلا payment_account_id (النقدي والبنكي) يحملان نفس
     * الاسم وكلاهما كان يُرسَل، فاختيار «الخزنة» يَصل بمعرّف بنكي فيسقط إلى
     * الدرج الافتراضي — والموظف يُعجَز بمبلغ خرج من خزنة الفندق.
     */
    public function test_a_container_that_does_not_match_the_method_is_refused(): void
    {
        $this->shift();

        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 5000, 'category' => 'other', 'recipient_name' => 'بقالة',
            'expense_date' => today()->toDateString(),
            'payment_method' => 'cash', 'payment_account_id' => $this->bank()->id,
        ])->assertSessionHasErrors('payment_account_id');

        $this->assertSame(0, Expense::where('recipient_name', 'بقالة')->count());
    }

    /** والراتب المصروف من الخزنة لا يُنقص درج الوردية ولا يُعجِزها. */
    public function test_a_salary_paid_from_the_safe_does_not_deficit_the_shift(): void
    {
        $employee = $this->employee();
        $shift    = $this->shift();

        $this->actingAs($this->admin())->post(route('expenses.store'), [
            'amount' => 80000, 'category' => 'salary', 'recipient_name' => $employee->name,
            'employee_id' => $employee->id, 'salary_month' => 9, 'salary_year' => 2025,
            'expense_date' => today()->toDateString(),
            'payment_method' => 'cash', 'payment_account_id' => $this->safe()->id,
        ])->assertRedirect(route('expenses.index'));

        $withdrawal = CashWithdrawal::whereHas('expense', fn ($q) => $q->where('employee_id', $employee->id))->firstOrFail();

        $this->assertSame('general_safe', $withdrawal->funding_source);
        $this->assertNull($withdrawal->shift_id);

        app(\App\Services\ShiftService::class)->computeTotals($shift);
        $shift->refresh();

        $this->assertEqualsWithDelta(0, $shift->total_withdrawals_yer, 0.01);
        $this->assertEqualsWithDelta(0, $shift->net_balance_yer, 0.01, 'ظهر عجز بمبلغٍ خرج من الخزنة');
    }

    /**
     * وسحبيةٌ قديمة تحمل shift_id ومصدرَ تمويلٍ «الصندوق العام» معاً لا تُنقص
     * الدرج بعد إعادة الاحتساب.
     *
     * قبل فصل المسارين كان مصروف الخزنة يُربط بالوردية كأي سحب، فسجلاتٌ قائمة
     * في النظام تحمل الاثنين — وهي ما ظلّ يُعجِز الموظف ولو صار النموذج سليماً.
     */
    public function test_a_legacy_safe_withdrawal_linked_to_a_shift_no_longer_deficits_it(): void
    {
        $shift = $this->shift();

        CashWithdrawal::create([
            'shift_id' => $shift->id, 'funding_source' => 'general_safe',
            'payment_account_id' => $this->safe()->id,
            'amount' => 60000, 'currency' => 'YER', 'withdrawal_date' => now(),
            'withdrawn_by_name' => 'راتب قديم', 'handed_by_name' => $this->admin()->name,
            'withdrawal_type' => 'expense',
        ]);

        // وسحبية من الدرج في نفس الوردية تبقى محتسبة
        CashWithdrawal::create([
            'shift_id' => $shift->id, 'funding_source' => 'shift',
            'payment_account_id' => $this->drawer()->id,
            'amount' => 4000, 'currency' => 'YER', 'withdrawal_date' => now(),
            'withdrawn_by_name' => 'مورّد', 'handed_by_name' => $this->admin()->name,
            'withdrawal_type' => 'expense',
        ]);

        app(\App\Services\ShiftService::class)->computeTotals($shift);

        $this->assertEqualsWithDelta(4000, $shift->refresh()->total_withdrawals_yer, 0.01);
        // ولا تختفي من سجلّ الوردية — ظاهرةٌ غير محتسبة
        $this->assertSame(2, $shift->withdrawals()->count());
    }

    /** ونموذج المصروف لا يُرسل إلا وعاء الطريقة المختارة. */
    public function test_the_expense_form_disables_the_hidden_container_field(): void
    {
        $this->actingAs($this->admin())->get(route('expenses.create'))
            ->assertOk()
            ->assertSee(":disabled=\"method !== 'cash'\"", false)
            ->assertSee(":disabled=\"method !== 'bank_transfer'\"", false);
    }
}
