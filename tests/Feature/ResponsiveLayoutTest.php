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

    /**
     * شبكات بأعمدة ثابتة بلا بادئة md: كانت تبقى بعدد أعمدتها على الجوال،
     * فيصير عرض العمود نحو 100px. قِيست 15 صفحة بها هذا العيب.
     */
    public function test_fixed_column_grids_collapse_on_small_screens(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('main .grid-cols-3,', $html);
        $this->assertStringContainsString('main .grid-cols-12 { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }', $html);
    }

    /** والنماذج تنهار إلى عمود واحد: حقل في عمود ضيّق لا يُكتب. */
    public function test_form_grids_become_a_single_column(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('main .grid:has(> * input)', $html);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) !important;', $html);
    }

    /** نصّ ممنوع من الالتفاف كان يتجاوز حافة الشاشة داخل البطاقة. */
    public function test_nowrap_text_wraps_inside_cards(): void
    {
        $this->assertStringContainsString(
            'table[data-cards] tbody td * { white-space: normal !important; }',
            $this->page()
        );
    }

    public function test_the_page_declares_a_mobile_viewport(): void
    {
        $this->assertMatchesRegularExpression(
            '/<meta name="viewport"[^>]*width=device-width/',
            $this->page()
        );
    }

    /* ═══════════════════ الدُرج والتمرير على الجوال ═══════════════════ */

    private function themeCss(): string
    {
        return file_get_contents(public_path('css/app-theme.css'));
    }

    /**
     * الدُرج على الجوال ينزلق بعرضه الكامل. طيّه بتقليص العرض كان يضغط أسماء
     * الأقسام أثناء الحركة فتتراكب حروفها عند كل ضغطة على زر القائمة.
     */
    public function test_the_mobile_drawer_slides_instead_of_shrinking(): void
    {
        $html = $this->page();
        $css  = $this->themeCss();

        $this->assertStringContainsString("isMobile ? 'app-drawer' : ''", $html);
        $this->assertStringContainsString("(isMobile && !sidebarOpen) ? 'app-drawer-closed' : ''", $html);

        $this->assertStringContainsString('transform: translateX(0)', $css);
        $this->assertStringContainsString('transform: translateX(100%)', $css);
        // الطيّ بالعرض يبقى للحاسوب وحده
        $this->assertStringContainsString("(!isMobile && !sidebarOpen) ? 'w-0 overflow-hidden' : ''", $html);
    }

    /** وعلى الحاسوب يُخفى محتوى القائمة فور طيّها فلا تتراكب أسطرها. */
    public function test_the_collapsing_desktop_sidebar_hides_its_content_at_once(): void
    {
        $this->assertStringContainsString('data-sidebar', $this->page());
        $this->assertStringContainsString(
            'aside[data-sidebar][data-collapsed] > * { visibility: hidden; }',
            $this->themeCss()
        );
    }

    /**
     * 100vh على متصفحات الجوال يحسب شريط العنوان ضمن الطول، فيُقتطع أسفل
     * الصفحة ولا يصل التمرير آخر التفاصيل. 100dvh تتبع المساحة المرئية فعلاً.
     */
    public function test_the_shell_uses_the_visible_viewport_height(): void
    {
        $this->assertStringContainsString('app-shell', $this->page());
        $this->assertStringContainsString('.app-shell { height: 100vh; height: 100dvh; }', $this->themeCss());
    }

    /** ومساحة أسفل المحتوى كي لا يختفي آخر زر خلف شريط المتصفح. */
    public function test_the_content_area_scrolls_comfortably_on_touch(): void
    {
        $css = $this->themeCss();

        $this->assertStringContainsString('-webkit-overflow-scrolling: touch;', $css);
        $this->assertStringContainsString('overscroll-behavior-y: contain;', $css);
        $this->assertStringContainsString('env(safe-area-inset-bottom)', $css);
        $this->assertStringContainsString('app-main', $this->page());
    }

    /** نافذة الملاحظات تُثبَّت أسفل الشاشة على الجوال فتُقرأ بلا سحب أفقي. */
    public function test_the_notes_popover_becomes_a_bottom_sheet_on_mobile(): void
    {
        $css = $this->themeCss();

        $this->assertStringContainsString('#notesPopover', $css);
        $this->assertStringContainsString('bottom: 0 !important;', $css);
    }
}
