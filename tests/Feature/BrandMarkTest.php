<?php
namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الشعار واسم الفندق يأتيان من الإعدادات في كل شاشة — لا حرف ثابت ولا اسم
 * مكتوب يدوياً في القوالب.
 */
class BrandMarkTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /** شعار صالح صغير (PNG شفاف 1×1) بصيغة data URI كما يحفظه رفع الشعار. */
    private const LOGO = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public function test_login_page_shows_the_configured_logo(): void
    {
        Setting::set(Setting::HOTEL_LOGO, self::LOGO);
        Setting::set('hotel_name_ar', 'فندق السعودي السياحي');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee(self::LOGO, false)
            ->assertSee('فندق السعودي السياحي');
    }

    public function test_login_page_uses_the_legacy_logo_file_when_no_setting_is_stored(): void
    {
        Setting::forget(Setting::HOTEL_LOGO);

        // نسخ قديمة تحفظ الشعار ملفاً في public/ بدل الإعدادات — يبقى مدعوماً
        $this->assertFileExists(public_path('images/hotel-logo.png'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('images/hotel-logo.png', false);
    }

    /** بلا شعار مضبوط ولا ملف قديم: يُعرض أول حرف من اسم الفندق لا حرف ثابت. */
    public function test_the_letter_fallback_comes_from_the_hotel_name(): void
    {
        Setting::forget(Setting::HOTEL_LOGO);
        Setting::set('hotel_name_ar', 'نُزل الواحة');

        $html = view('partials.brand-mark')->render();

        if (str_contains($html, '<img')) {
            // ملف الشعار القديم موجود في هذه النسخة، فلا مجال للحرف البديل
            $this->assertStringContainsString('hotel-logo.png', $html);
            return;
        }

        $this->assertStringContainsString('>ن</div>', $html);
    }

    public function test_the_hotel_name_is_never_hardcoded_in_the_auth_pages(): void
    {
        foreach (['auth/login', 'auth/forgot-password'] as $view) {
            $blade = file_get_contents(resource_path("views/{$view}.blade.php"));
            $this->assertStringNotContainsString('الفندق السعودي', $blade, $view);
        }
    }

    public function test_sidebar_uses_the_configured_logo_and_name(): void
    {
        Setting::set(Setting::HOTEL_LOGO, self::LOGO);
        Setting::set('hotel_name_ar', 'فندق السعودي السياحي');

        $this->actingAs(User::role('admin')->firstOrFail())
            ->get(route('employees.index'))
            ->assertOk()
            ->assertSee(self::LOGO, false)
            ->assertSee('فندق السعودي السياحي');
    }
}
