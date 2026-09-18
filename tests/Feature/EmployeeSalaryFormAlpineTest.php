<?php
namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * قوس ناقص في x-data يُسقط مكوّن Alpine كاملاً بصمت: لا رسالة خطأ في الصفحة،
 * لكن x-model يُفرِّغ حقل الراتب فيجده الموظف فارغاً عند التعديل ويظن أن
 * الحقل "لا يقبل الكتابة". لا تكشفه اختبارات HTML العادية، فنفحص توازن
 * الأقواس في كل تعبير x-data نصّياً.
 */
class EmployeeSalaryFormAlpineTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function employee(float $base = 100000, float $food = 60000): Employee
    {
        return Employee::create([
            'name' => 'موظف النموذج', 'position' => 'استقبال', 'base_salary' => $base,
            'food_allowance' => $food, 'hire_date' => today()->subYear(), 'is_active' => true,
        ]);
    }

    /** يستخرج تعابير x-data من HTML ويتحقق من توازن أقواسها. */
    private function assertBalancedAlpineExpressions(string $html, string $context): void
    {
        preg_match_all('/x-data="([^"]*)"/', $html, $matches);
        $this->assertNotEmpty($matches[1], "لا يوجد x-data في {$context}");

        foreach ($matches[1] as $expression) {
            $decoded = html_entity_decode($expression, ENT_QUOTES);
            $depth   = 0;

            foreach (str_split($decoded) as $char) {
                if ($char === '{') { $depth++; }
                if ($char === '}') { $depth--; }
                $this->assertGreaterThanOrEqual(0, $depth, "قوس إغلاق زائد في x-data — {$context}");
            }

            $this->assertSame(0, $depth, "أقواس غير متوازنة في x-data ({$context}): {$decoded}");
        }
    }

    public function test_employee_edit_form_has_a_valid_alpine_component(): void
    {
        $employee = $this->employee();

        $html = $this->actingAs($this->admin())
            ->get(route('employees.edit', $employee))
            ->assertOk()
            ->getContent();

        $this->assertBalancedAlpineExpressions($html, 'شاشة تعديل الموظف');
    }

    public function test_employee_create_form_has_a_valid_alpine_component(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('employees.create'))
            ->assertOk()
            ->getContent();

        $this->assertBalancedAlpineExpressions($html, 'شاشة إضافة موظف');
    }

    /** حقل الراتب يصل للصفحة بقيمته المحفوظة، لا فارغاً. */
    public function test_edit_form_prefills_the_saved_salary(): void
    {
        $employee = $this->employee(100000, 60000);

        $html = $this->actingAs($this->admin())
            ->get(route('employees.edit', $employee))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/name="base_salary"[^>]*value="100000(\.00)?"/',
            $html,
            'حقل الراتب الأساسي يجب أن يصل معبَّأً بقيمته المحفوظة'
        );
        // قيمة Alpine الابتدائية تأتي من نفس القيمة، فلا يُفرَّغ الحقل بعد التهيئة
        $this->assertStringContainsString('base: 100000', $html);
        $this->assertStringContainsString('food: 60000', $html);
    }
}
