<?php
namespace Tests\Feature;

use App\Models\User;
use App\Services\PermissionService;
use App\Support\ReportRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صلاحية واحدة (reports.view) كانت تفتح كل التقارير، فيرى موظف الاستقبال
 * الأرباح والرواتب والصندوق العام. لكل تقرير الآن صلاحيته، يمنحها المدير.
 */
class ReportPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function staffWith(array $granted): User
    {
        $user = User::role('receptionist')->firstOrFail();
        $admin = User::role('admin')->firstOrFail();

        PermissionService::toggle($user, 'reports.view', true, $admin);
        foreach ($granted as $key) {
            PermissionService::toggle($user, $key, true, $admin);
        }

        return $user->fresh();
    }

    public function test_report_access_is_denied_by_default(): void
    {
        $user = $this->staffWith([]);

        // البوابة العامة مفتوحة، لكن التقرير المالي يبقى ممنوعاً
        $this->actingAs($user)->get(route('reports.profitLoss'))->assertRedirect();
        $this->actingAs($user)->get(route('reports.generalSafe'))->assertRedirect();
        $this->actingAs($user)->get(route('reports.debts'))->assertRedirect();
    }

    public function test_granting_one_report_does_not_open_the_others(): void
    {
        $user = $this->staffWith(['reports.rooms_inventory']);

        $this->actingAs($user)->get(route('reports.amAli'))->assertOk();

        $this->actingAs($user)->get(route('reports.profitLoss'))->assertRedirect();
        $this->actingAs($user)->get(route('reports.hrHub'))->assertRedirect();
    }

    /** التصدير محكوم بالصلاحية نفسها — لا يُلتفّ عليه برابط PDF مباشر. */
    public function test_the_export_route_is_gated_like_the_page(): void
    {
        $user = $this->staffWith([]);

        $this->actingAs($user)->get(route('reports.debts.pdf'))->assertRedirect();
        $this->actingAs($user)->get(route('reports.generalSafe.pdf'))->assertRedirect();

        $allowed = $this->staffWith(['reports.debts']);
        $this->actingAs($allowed)->get(route('reports.debts.pdf'))->assertOk();
    }

    public function test_the_admin_still_reaches_every_report(): void
    {
        $admin = User::role('admin')->firstOrFail();

        foreach (['reports.profitLoss', 'reports.generalSafe', 'reports.debts', 'reports.amAli'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    /** القائمة الجانبية لا تعرض باباً مغلقاً. */
    public function test_the_sidebar_hides_reports_the_user_cannot_open(): void
    {
        $user = $this->staffWith(['reports.rooms_inventory']);

        $page = $this->actingAs($user)->get(route('reports.amAli'))->assertOk();

        $page->assertSee('جرد غرف اليومية');
        $page->assertDontSee('الأرباح والخسائر');
        $page->assertDontSee('التقارير المالية');
    }

    public function test_every_report_has_its_own_permission_in_the_catalogue(): void
    {
        $all = PermissionService::all();

        foreach (ReportRegistry::REPORTS as $key => $report) {
            $this->assertArrayHasKey($report['permission'], $all, "التقرير {$key} بلا صلاحية معرَّفة");
        }

        // ومصنَّفة بحسب من يعنيه التقرير
        $groups = collect($all)->pluck('group')->unique();
        foreach (['📊 تقارير الاستقبال', '📊 تقارير الإدارة', '📊 تقارير المالية'] as $group) {
            $this->assertTrue($groups->contains($group), "المجموعة {$group} غير موجودة");
        }
    }

    /** التقارير ذات القوالب الثابتة محدَّدة صراحةً ولا تقبل التخصيص. */
    public function test_the_locked_reports_are_the_three_named_ones(): void
    {
        $locked = collect(ReportRegistry::REPORTS)->filter(fn ($r) => !empty($r['locked']))->keys();

        $this->assertEqualsCanonicalizing(
            ['rooms_inventory', 'reservations', 'government'],
            $locked->all()
        );
    }

    public function test_the_report_was_renamed_to_daily_rooms_inventory(): void
    {
        $admin = User::role('admin')->firstOrFail();

        $page = $this->actingAs($admin)->get(route('reports.amAli'))->assertOk();

        $page->assertSee('جرد غرف اليومية');
        $page->assertDontSee('تقرير عم علي');
    }
}
