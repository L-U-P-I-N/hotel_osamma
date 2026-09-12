<?php
namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * كشف المسحوبات يُصدَّر لكل موظف على حدة، ويُصدَّر لكل الموظفين دفعةً واحدة
 * مرقَّمةً ومرتَّبةً أبجدياً بالاسم — مع مَن صرف المبلغ وفي أي وردية.
 */
class EmployeeStatementExportTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function employee(string $name, float $base = 60000): Employee
    {
        return Employee::create([
            'name' => $name, 'position' => 'استقبال', 'base_salary' => $base,
            'hire_date' => today()->subYear(), 'is_active' => true,
        ]);
    }

    private function withdrawal(Employee $employee, User $by, Shift $shift, float $amount): Expense
    {
        return Expense::create([
            'amount'       => $amount,
            'currency'     => 'YER',
            'category'     => 'employee_withdrawal',
            'description'  => 'سلفة على الراتب',
            'expense_date' => today(),
            'paid_by'      => $by->id,
            'employee_id'  => $employee->id,
            'shift_id'     => $shift->id,
        ]);
    }

    private function openShift(User $user): Shift
    {
        return Shift::create([
            'user_id' => $user->id, 'shift_date' => today(),
            'started_at' => now()->subHour(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);
    }

    public function test_single_employee_withdrawals_export_returns_a_pdf(): void
    {
        $admin    = $this->admin();
        $employee = $this->employee('سالم أحمد');
        $this->withdrawal($employee, $admin, $this->openShift($admin), 5000);

        $response = $this->actingAs($admin)->get(route('employees.withdrawals.pdf', [
            'employee' => $employee->id, 'month' => now()->month, 'year' => now()->year,
        ]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_withdrawals_screen_offers_the_export_button(): void
    {
        $employee = $this->employee('سالم أحمد');

        $this->actingAs($this->admin())
            ->get(route('employees.withdrawals', $employee))
            ->assertOk()
            ->assertSee("/employees/{$employee->id}/withdrawals/pdf", false);
    }

    public function test_all_employees_export_numbers_rows_alphabetically(): void
    {
        // تُنشأ بترتيب مقلوب عمداً: الترقيم يجب أن يتبع الاسم لا ترتيب الإدخال
        $this->employee('ياسر علي');
        $this->employee('أحمد سالم');

        $rows = $this->actingAs($this->admin())
            ->get(route('employees.statements'))
            ->assertOk()
            ->viewData('rows');

        $names = $rows->pluck('employee.name')->all();
        $sorted = collect($names)->sortBy(null, SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        $this->assertSame($sorted, $names, 'الصفوف يجب أن تكون مرتَّبة أبجدياً');
        $this->assertSame(range(1, count($names)), $rows->pluck('seq')->all());
    }

    public function test_all_employees_pdf_export_renders(): void
    {
        $admin = $this->admin();
        $this->withdrawal($this->employee('أحمد سالم'), $admin, $this->openShift($admin), 3000);

        $response = $this->actingAs($admin)->get(route('employees.statements.pdf'));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_statement_shows_monthly_salary_breakdown(): void
    {
        $admin    = $this->admin();
        $employee = $this->employee('سالم أحمد', 60000);
        $this->withdrawal($employee, $admin, $this->openShift($admin), 10000);

        $data = $this->actingAs($admin)
            ->get(route('employees.statement', ['employee' => $employee->id]))
            ->assertOk()
            ->viewData('monthly');

        $current = collect($data)->firstWhere('month', now()->month);

        $this->assertNotNull($current);
        $this->assertSame(60000.0, (float) $current['base']);
        $this->assertSame(10000.0, (float) $current['chargeable']);
        $this->assertSame(50000.0, (float) $current['remaining']);
    }
}
