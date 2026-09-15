<?php
namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * إضافة موظف: ترك صرفية الطعام فارغة يجب أن يُحفظ كصفر لا أن يسقط بخطأ 500،
 * والراتب الإجمالي (الأساسي + الصرفية) يُعرض مع الموظف أينما ذُكر راتبه.
 */
class EmployeeSalaryFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    public function test_empty_food_allowance_is_saved_as_zero(): void
    {
        $this->actingAs($this->admin())
            ->post(route('employees.store'), [
                'name'           => 'موظف بلا صرفية',
                'position'       => 'استقبال',
                'base_salary'    => 100000,
                'food_allowance' => '',   // الحقل متروك فارغاً في النموذج
                'hire_date'      => today()->toDateString(),
                'is_active'      => 1,
            ])
            ->assertRedirect(route('employees.index'));

        $employee = Employee::where('name', 'موظف بلا صرفية')->firstOrFail();

        $this->assertSame(0.0, (float) $employee->food_allowance);
        $this->assertSame(100000.0, $employee->total_salary);
    }

    public function test_total_salary_adds_the_food_allowance_to_the_base(): void
    {
        $this->actingAs($this->admin())
            ->post(route('employees.store'), [
                'name'           => 'موظف بصرفية',
                'position'       => 'استقبال',
                'base_salary'    => 100000,
                'food_allowance' => 60000,
                'hire_date'      => today()->toDateString(),
                'is_active'      => 1,
            ])
            ->assertRedirect(route('employees.index'));

        $employee = Employee::where('name', 'موظف بصرفية')->firstOrFail();

        $this->assertSame(160000.0, $employee->total_salary);

        // الإجمالي معروض في القائمة وفي كشف الحساب معاً
        $this->actingAs($this->admin())->get(route('employees.index'))
            ->assertOk()->assertSee('160,000');
        $this->actingAs($this->admin())->get(route('employees.statement', $employee))
            ->assertOk()->assertSee('الراتب الإجمالي');
    }

    public function test_clearing_the_food_allowance_on_update_keeps_it_zero(): void
    {
        $employee = Employee::create([
            'name' => 'موظف للتعديل', 'position' => 'استقبال', 'base_salary' => 80000,
            'food_allowance' => 20000, 'hire_date' => today(), 'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('employees.update', $employee), [
                'name'           => $employee->name,
                'position'       => $employee->position,
                'base_salary'    => 80000,
                'food_allowance' => '',
                'hire_date'      => $employee->hire_date->toDateString(),
                'is_active'      => 1,
            ])
            ->assertRedirect(route('employees.index'));

        $this->assertSame(0.0, (float) $employee->fresh()->food_allowance);
    }
}
