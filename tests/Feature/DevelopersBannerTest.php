<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * أسماء مطوّري النظام وأرقامهم في شريط الأعلى — الشريط نفسه الذي فيه زرّ تسجيل
 * الدخول وأيقونتا التحديث والوضع الليلي — فتظهر في كل صفحة لا في صفحة واحدة.
 */
class DevelopersBannerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function pageAt(string $route): string
    {
        return $this->actingAs(User::role('admin')->firstOrFail())
            ->get(route($route))
            ->assertOk()
            ->getContent();
    }

    public function test_the_team_and_developer_names_are_in_the_top_bar(): void
    {
        $html = $this->pageAt('dashboard');

        $this->assertStringContainsString('أسامه عادل باحشوان', $html);
        $this->assertStringContainsString('774303713', $html);
        $this->assertStringContainsString('محمد ناجي مسونق', $html);
        $this->assertStringContainsString('770794503', $html);
        $this->assertStringContainsString(config('developers.team_name'), $html);
    }

    /** في الشريط لا في جسم الصفحة — بجوار أيقونتَي التحديث والوضع الليلي. */
    public function test_it_sits_inside_the_same_bar_as_the_refresh_and_theme_icons(): void
    {
        $html = $this->pageAt('dashboard');

        $bar = substr(
            $html,
            strpos($html, 'app-topbar'),
            strpos($html, '<!-- Flash messages -->') - strpos($html, 'app-topbar')
        );

        $this->assertStringContainsString('تبديل الوضع الليلي / النهاري', $bar);
        $this->assertStringContainsString('تحديث الصفحة', $bar);
        $this->assertStringContainsString('أسامه عادل باحشوان', $bar);
        $this->assertStringContainsString('محمد ناجي مسونق', $bar);
    }

    /** يبقى ظاهراً بعد الانتقال بين الصفحات. */
    public function test_it_survives_navigation_to_other_pages(): void
    {
        foreach (['reservations.expiring', 'rooms.index', 'floors.index', 'shifts.index'] as $route) {
            $this->assertStringContainsString('أسامه عادل باحشوان', $this->pageAt($route), $route);
        }
    }

    /** الرقم رابط اتصال مباشر — أسرع ما يحتاجه الموظف وقت المشكلة. */
    public function test_the_phone_numbers_are_click_to_call_links(): void
    {
        $html = $this->pageAt('dashboard');

        $this->assertStringContainsString('href="tel:774303713"', $html);
        $this->assertStringContainsString('href="tel:770794503"', $html);
    }

    /** وصفحة تسجيل الدخول أيضاً: الحاجة لأرقامهم أشدّ حين يتعذّر الدخول. */
    public function test_the_login_page_shows_them_too(): void
    {
        $html = $this->get(route('login'))->assertOk()->getContent();

        $this->assertStringContainsString('أسامه عادل باحشوان', $html);
        $this->assertStringContainsString('770794503', $html);
    }
}
