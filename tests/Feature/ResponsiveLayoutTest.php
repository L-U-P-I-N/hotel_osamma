<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تهيئة الواجهة للجوال واللوحي.
 *
 * النظام كان مصمَّماً لشاشة حاسوب: القائمة الجانبية تدفع المحتوى فلا يبقى
 * منه على جوال 390px إلا 150px، والجداول تخفي أعمدتها في تمرير أفقي.
 * هذه الاختبارات تحرس القواعد التي عالجت ذلك من الحذف سهواً.
 */
class ResponsiveLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function page(): string
    {
        return $this->actingAs(User::role('admin')->firstOrFail())
            ->get(route('dashboard'))
            ->assertOk()
            ->getContent();
    }

    /** القائمة تنزلق فوق المحتوى على الجوال بدل أن تقتطع عرضه. */
    public function test_the_sidebar_is_an_overlay_drawer_on_small_screens(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('isMobile', $html);
        $this->assertStringContainsString("'fixed inset-y-0 right-0 z-50 shadow-2xl'", $html);
        // غطاء يُعتّم الخلفية ويُغلق الدُرج باللمس خارجه
        $this->assertStringContainsString('isMobile && sidebarOpen', $html);
        $this->assertStringContainsString('closeOnMobile()', $html);
    }

    /** وتعود قائمةً جانبية تدفع المحتوى على الحاسوب. */
    public function test_the_desktop_sidebar_still_pushes_the_content(): void
    {
        $this->assertStringContainsString("'flex-shrink-0'", $this->page());
    }

    public function test_the_mobile_ui_rules_are_loaded_on_every_page(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('table[data-cards]', $html, 'قواعد البطاقات غير محمَّلة');
        $this->assertStringContainsString('@media (max-width: 767px)', $html);
        $this->assertStringContainsString('data-no-cards', $html, 'لا توجد وسيلة لاستثناء جدول');
    }

    /** الخانة الفارغة تُخفى كي لا تطول البطاقة بصفوف بلا محتوى. */
    public function test_empty_cells_are_hidden_in_card_view(): void
    {
        $this->assertStringContainsString('td[data-empty] { display: none; }', $this->page());
    }

    /** أشرطة الأزرار تلتفّ على الجوال بدل أن يخرج آخر زر خارج الشاشة. */
    public function test_action_bars_wrap_on_small_screens(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('main .flex:not(.flex-nowrap):not(.flex-col) { flex-wrap: wrap; }', $html);
    }

    /** العنوان يُقتطع بدل أن ينكسر سطرين ويزاحم أزرار الشريط العلوي. */
    public function test_the_topbar_is_compact_on_small_screens(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('min-w-0 truncate', $html);
        $this->assertStringContainsString('px-3 sm:px-6', $html);
        // كلمة «تسجيل دخول» تُخفى ويبقى رمزها
        $this->assertStringContainsString('hidden sm:inline', $html);
    }

    public function test_the_page_declares_a_mobile_viewport(): void
    {
        $this->assertMatchesRegularExpression(
            '/<meta name="viewport"[^>]*width=device-width/',
            $this->page()
        );
    }
}
