<?php
namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodAllowanceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    /** موظف: راتب 100 ألف + صرفية طعام 60 ألف شهرياً */
    private function employee(float $salary = 100000, float $allowance = 60000): Employee
    {
        return Employee::create([
            'name'           => 'موظف اختبار',
            'position'       => 'استقبال',
            'base_salary'    => $salary,
            'food_allowance' => $allowance,
            'hire_date'      => now()->subYear(),
            'is_active'      => true,
        ]);
    }

    private function spend(Employee $employee, float $amount, string $category, ?string $date = null): Expense
    {
        return Expense::create([
            'amount'       => $amount,
            'currency'     => 'YER',
            'category'     => $category,
            'description'  => 'اختبار',
            'expense_date' => $date ?? now()->toDateString(),
            'paid_by'      => $this->admin()->id,
            'employee_id'  => $employee->id,
        ]);
    }

    public function test_food_spending_does_not_touch_the_salary(): void
    {
        $employee = $this->employee();
        $month = (int) now()->month;
        $year  = (int) now()->year;

        // صرف 45 ألف طعام خلال الشهر — ضمن الصرفية
        $this->spend($employee, 20000, 'food');
        $this->spend($employee, 25000, 'food');

        $food = $employee->foodAllowanceSummary($month, $year);
        $this->assertEqualsWithDelta(60000, $food['allowance'], 0.01);
        $this->assertEqualsWithDelta(45000, $food['spent'], 0.01);
        $this->assertEqualsWithDelta(15000, $food['remaining'], 0.01);
        $this->assertEqualsWithDelta(0, $food['overspend'], 0.01);

        // لا شيء يُخصم من الراتب
        $this->assertEqualsWithDelta(0, $employee->withdrawalsTotalForMonth($month, $year), 0.01);
    }

    public function test_only_the_excess_over_the_allowance_is_deducted(): void
    {
        $employee = $this->employee();
        $month = (int) now()->month;
        $year  = (int) now()->year;

        $this->spend($employee, 75000, 'food'); // تجاوز 15 ألف

        $food = $employee->foodAllowanceSummary($month, $year);
        $this->assertEqualsWithDelta(0, $food['remaining'], 0.01);
        $this->assertEqualsWithDelta(15000, $food['overspend'], 0.01);
        $this->assertEqualsWithDelta(15000, $employee->withdrawalsTotalForMonth($month, $year), 0.01);
    }

    public function test_non_food_spending_is_still_deducted_in_full(): void
    {
        $employee = $this->employee();
        $month = (int) now()->month;
        $year  = (int) now()->year;

        $this->spend($employee, 30000, 'food');   // ضمن الصرفية
        $this->spend($employee, 20000, 'salary'); // سلفة على الراتب
        $this->spend($employee, 5000,  'other');

        // الطعام لا يُخصم، والباقي يُخصم كاملاً
        $this->assertEqualsWithDelta(25000, $employee->withdrawalsTotalForMonth($month, $year), 0.01);
    }

    /** الصرفية تتجدد كل شهر ولا تُرحَّل */
    public function test_the_allowance_resets_every_month(): void
    {
        $employee = $this->employee();
        $lastMonth = now()->subMonthNoOverflow();

        $this->spend($employee, 60000, 'food', $lastMonth->toDateString());

        $spentLast = $employee->foodSpentForMonth((int) $lastMonth->month, (int) $lastMonth->year);
        $this->assertEqualsWithDelta(60000, $spentLast, 0.01);

        // الشهر الحالي يبدأ من الصفر بصرفية كاملة
        $thisMonth = $employee->foodAllowanceSummary((int) now()->month, (int) now()->year);
        $this->assertEqualsWithDelta(0, $thisMonth['spent'], 0.01);
        $this->assertEqualsWithDelta(60000, $thisMonth['remaining'], 0.01);
    }

    public function test_employee_form_saves_the_allowance(): void
    {
        $this->actingAs($this->admin())->post('/employees', [
            'name'           => 'موظف جديد',
            'position'       => 'استقبال',
            'base_salary'    => 100000,
            'food_allowance' => 60000,
            'hire_date'      => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $employee = Employee::where('name', 'موظف جديد')->firstOrFail();
        $this->assertEqualsWithDelta(60000, (float) $employee->food_allowance, 0.01);
        $this->assertEqualsWithDelta(100000, (float) $employee->base_salary, 0.01);
    }

    public function test_withdrawals_page_separates_the_two_budgets(): void
    {
        $employee = $this->employee();
        $this->spend($employee, 45000, 'food');
        $this->spend($employee, 10000, 'other');

        $this->actingAs($this->admin())
            ->get("/employees/{$employee->id}/withdrawals")
            ->assertOk()
            ->assertSee('صرفية الطعام والشراب', false)
            ->assertSee('تتجدد كل شهر ولا تُخصم من الراتب الأساسي', false);
    }
}
