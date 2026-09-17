<?php
namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Salary;
use App\Models\SalaryDeduction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * خصم من راتب موظف: يُسجَّل وقت حدوثه بسببه وتاريخه ومَن سجّله، ويُخصم تلقائياً
 * من قسيمة راتب شهره. القسيمة المدفوعة لا تُمسّ (أرقامها صُرفت فعلاً).
 */
class SalaryDeductionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function employee(float $base = 100000): Employee
    {
        return Employee::create([
            'name' => 'موظف الخصم', 'position' => 'استقبال', 'base_salary' => $base,
            'food_allowance' => 0, 'hire_date' => today()->subYear(), 'is_active' => true,
        ]);
    }

    private function slip(Employee $employee, string $status = 'draft'): Salary
    {
        return Salary::create([
            'employee_id' => $employee->id, 'month' => now()->month, 'year' => now()->year,
            'base_salary' => $employee->base_salary, 'bonuses' => 0, 'deductions' => 0,
            'net_salary' => $employee->base_salary, 'status' => $status,
        ]);
    }

    private function deductionPayload(array $overrides = []): array
    {
        return array_merge([
            'amount'         => 15000,
            'reason'         => 'lateness',
            'description'    => 'تأخير متكرر',
            'deduction_date' => today()->toDateString(),
        ], $overrides);
    }

    public function test_admin_records_a_deduction_with_reason_date_and_author(): void
    {
        $admin    = $this->admin();
        $employee = $this->employee();

        $this->actingAs($admin)
            ->post(route('employees.deductions.store', $employee), $this->deductionPayload())
            ->assertRedirect();

        $deduction = SalaryDeduction::firstOrFail();

        $this->assertSame($employee->id, $deduction->employee_id);
        $this->assertSame(15000.0, (float) $deduction->amount);
        $this->assertSame('lateness', $deduction->reason);
        $this->assertSame($admin->id, $deduction->created_by);
        $this->assertSame(15000.0, $employee->recordedDeductionsForMonth(now()->month, now()->year));
    }

    public function test_deduction_updates_an_unpaid_salary_slip_immediately(): void
    {
        $employee = $this->employee(100000);
        $slip     = $this->slip($employee);

        $this->actingAs($this->admin())
            ->post(route('employees.deductions.store', $employee), $this->deductionPayload(['amount' => 15000]));

        $slip->refresh();

        $this->assertSame(15000.0, (float) $slip->recorded_deductions);
        $this->assertSame(85000.0, (float) $slip->net_salary);
    }

    public function test_a_paid_slip_is_never_altered_by_a_later_deduction(): void
    {
        $employee = $this->employee(100000);
        $slip     = $this->slip($employee, 'paid');

        $this->actingAs($this->admin())
            ->post(route('employees.deductions.store', $employee), $this->deductionPayload());

        $slip->refresh();

        $this->assertSame(0.0, (float) $slip->recorded_deductions);
        $this->assertSame(100000.0, (float) $slip->net_salary);
    }

    public function test_deductions_beyond_the_salary_leave_a_negative_net(): void
    {
        $employee = $this->employee(100000);
        $slip     = $this->slip($employee);

        $this->actingAs($this->admin())
            ->post(route('employees.deductions.store', $employee), $this->deductionPayload(['amount' => 120000]));

        // الفارق يبقى ديناً على الموظف ولا يُصفَّر كي لا يضيع بلا أثر
        $this->assertSame(-20000.0, (float) $slip->refresh()->net_salary);
    }

    public function test_a_new_slip_picks_up_the_months_recorded_deductions(): void
    {
        $employee = $this->employee(100000);

        SalaryDeduction::create([
            'employee_id' => $employee->id, 'amount' => 25000, 'reason' => 'damage',
            'deduction_date' => today(), 'created_by' => $this->admin()->id,
        ]);

        $this->actingAs($this->admin())
            ->post(route('salaries.store'), [
                'employee_id' => $employee->id,
                'month'       => now()->month,
                'year'        => now()->year,
                'base_salary' => 100000,
                'bonuses'     => 0,
                'deductions'  => 0,
            ])
            ->assertRedirect();

        $slip = Salary::where('employee_id', $employee->id)->firstOrFail();

        $this->assertSame(25000.0, (float) $slip->recorded_deductions);
        $this->assertSame(75000.0, (float) $slip->net_salary);
    }

    public function test_deleting_a_deduction_recalculates_the_slip(): void
    {
        $employee = $this->employee(100000);
        $slip     = $this->slip($employee);

        $this->actingAs($this->admin())
            ->post(route('employees.deductions.store', $employee), $this->deductionPayload(['amount' => 15000]));

        $deduction = SalaryDeduction::firstOrFail();

        $this->actingAs($this->admin())
            ->delete(route('employees.deductions.destroy', [$employee, $deduction]))
            ->assertRedirect();

        $slip->refresh();

        $this->assertSame(0.0, (float) $slip->recorded_deductions);
        $this->assertSame(100000.0, (float) $slip->net_salary);
    }

    public function test_future_dated_and_zero_deductions_are_rejected(): void
    {
        $employee = $this->employee();

        $this->actingAs($this->admin())
            ->post(route('employees.deductions.store', $employee),
                $this->deductionPayload(['deduction_date' => today()->addDay()->toDateString()]))
            ->assertSessionHasErrors('deduction_date');

        $this->actingAs($this->admin())
            ->post(route('employees.deductions.store', $employee), $this->deductionPayload(['amount' => 0]))
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, SalaryDeduction::count());
    }

    public function test_deductions_screen_lists_the_records(): void
    {
        $employee = $this->employee();
        $this->actingAs($this->admin())
            ->post(route('employees.deductions.store', $employee), $this->deductionPayload());

        $this->actingAs($this->admin())
            ->get(route('employees.deductions', $employee))
            ->assertOk()
            ->assertSee('تأخير عن الدوام')
            ->assertSee('تأخير متكرر')
            ->assertSee('15,000');
    }

    public function test_statement_and_its_export_show_the_deductions(): void
    {
        $employee = $this->employee();
        $this->actingAs($this->admin())
            ->post(route('employees.deductions.store', $employee), $this->deductionPayload());

        $this->actingAs($this->admin())
            ->get(route('employees.statement', $employee))
            ->assertOk()
            ->assertSee('الخصومات المسجَّلة')
            ->assertSee('تأخير عن الدوام');

        $pdf = $this->actingAs($this->admin())->get(route('employees.statement.pdf', $employee));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
    }
}
