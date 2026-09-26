<?php
namespace Tests\Feature;

use App\Models\PdfTemplate;
use App\Models\User;
use App\Support\TemplateRenderer;
use App\Support\TemplateSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * مصمّم قوالب التصدير. القالب نصّ يكتبه المدير ويُخزَّن في قاعدة البيانات،
 * فأهم ما يُختبَر أنه لا يُنفَّذ كشيفرة: من يسرق كلمة مرور المدير يجب ألا
 * يملك بذلك تنفيذ أي شيء على خادم الفندق.
 */
class PdfTemplateDesignerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    /* ═══ المحرّك لا ينفّذ شيئاً ═══ */

    public static function dangerousTemplates(): array
    {
        return [
            'وسم PHP'        => ['<?php echo "اخترقت"; ?>'],
            'وسم PHP قصير'   => ['<?= system("id") ?>'],
            'سكربت'          => ['<script>fetch("//evil")</script>'],
            'إطار خارجي'     => ['<iframe src="//evil"></iframe>'],
            'حدث onclick'    => ['<div onclick="steal()">اضغط</div>'],
            'رابط javascript'=> ['<a href="javascript:steal()">رابط</a>'],
            'أمر Blade'      => ['@php echo 1; @endphp'],
            'تضمين Blade'    => ['@include("../../.env")'],
            'صورة خارجية'    => ['<img src="https://evil.test/x.png">'],
            'استيراد أنماط'  => ['<style>@import url(//evil);</style>'],
        ];
    }

    /** @dataProvider dangerousTemplates */
    public function test_a_dangerous_template_is_refused_with_an_arabic_reason(string $body): void
    {
        $problems = TemplateSanitizer::problems($body);

        $this->assertNotEmpty($problems, 'قُبل قالب خطر: ' . $body);
        $this->assertMatchesRegularExpression('/[\x{0600}-\x{06FF}]/u', $problems[0]);
    }

    /** @dataProvider dangerousTemplates */
    public function test_the_designer_rejects_a_dangerous_template_on_save(string $body): void
    {
        $this->actingAs($this->admin())
            ->post(route('pdf-templates.store'), [
                'name' => 'خبيث', 'report_key' => 'debts', 'body' => $body,
            ])
            ->assertSessionHasErrors('body');

        $this->assertSame(0, PdfTemplate::count(), 'حُفظ قالب خطر');
    }

    /**
     * حتى لو وصل نصّ خطر إلى المحرّك، فهو يمرّ كنصّ حرفي لا كشيفرة:
     * وسم PHP يخرج كما دخل ولا يُنفَّذ، والعنصر النائب وحده يُستبدَل.
     */
    public function test_the_engine_never_executes_what_it_renders(): void
    {
        $output = (new TemplateRenderer())->render(
            'قبل <?php $x = 2 + 2; echo $x; ?> بعد {{ الاسم }}',
            ['الاسم' => 'أحمد']
        );

        // لو نُفِّذ لظهر الناتج 4 واختفى الوسم
        $this->assertStringContainsString('<?php', $output, 'اختفى وسم PHP — ما يعني أنه فُسِّر');
        $this->assertStringNotContainsString('قبل 4 بعد', $output);
        $this->assertStringContainsString('أحمد', $output);
    }

    /** ولا يُفسَّر أي بناء من Blade داخل جسم القالب. */
    public function test_blade_syntax_is_not_interpreted_either(): void
    {
        $output = (new TemplateRenderer())->render('@php echo 99; @endphp {{ الاسم }}', ['الاسم' => 'سالم']);

        // الناتج هو المُدخل حرفياً ولم يتغيّر منه إلا العنصر النائب
        $this->assertSame('@php echo 99; @endphp سالم', $output);
    }

    /** قيم البيانات تُهرَّب، فلا يحقن نزيلٌ اسمُه HTML في المستند. */
    public function test_values_are_escaped_not_interpreted(): void
    {
        $output = (new TemplateRenderer())->render(
            '<p>{{ الاسم }}</p>',
            ['الاسم' => '<script>alert(1)</script>']
        );

        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('&lt;script&gt;', $output);
    }

    /* ═══ المحرّك يعمل ═══ */

    public function test_placeholders_loops_and_filters_render(): void
    {
        $output = (new TemplateRenderer())->render(
            '{{ العنوان }}|{% تكرار الصفوف %}{{ ترتيب }}:{{ الغرفة }}={{ المبلغ | رقم }};{% نهاية %}',
            [
                'العنوان' => 'تقرير',
                'الصفوف'  => [
                    ['الغرفة' => '101', 'المبلغ' => 25000],
                    ['الغرفة' => '203', 'المبلغ' => 40000],
                ],
            ]
        );

        $this->assertSame('تقرير|1:101=25,000;2:203=40,000;', $output);
    }

    public function test_a_conditional_hides_an_empty_section(): void
    {
        $engine = new TemplateRenderer();

        $this->assertSame('', $engine->render('{% إذا الخصم %}خصم{% نهاية %}', ['الخصم' => 0]));
        $this->assertSame('خصم', $engine->render('{% إذا الخصم %}خصم{% نهاية %}', ['الخصم' => 500]));
    }

    public function test_an_unknown_placeholder_renders_empty_not_an_error(): void
    {
        $output = (new TemplateRenderer())->render('[{{ غير_موجود }}]', []);

        $this->assertSame('[]', $output);
    }

    /* ═══ الصلاحية والقوالب الثابتة ═══ */

    public function test_only_an_admin_reaches_the_designer(): void
    {
        $this->actingAs(User::role('receptionist')->firstOrFail())
            ->get(route('pdf-templates.index'))
            ->assertRedirect();

        $this->actingAs($this->admin())->get(route('pdf-templates.index'))->assertOk();
    }

    /**
     * صفحات المصمّم تُعرَض فعلاً. اختبار الصلاحية وحده لا يكشف خطأ ترجمة
     * في Blade داخل صفحة المحرِّر، وقد وقع فعلاً: {{ }} متداخلة في تعبير.
     */
    public function test_the_designer_pages_render_their_editor(): void
    {
        $create = $this->actingAs($this->admin())->get(route('pdf-templates.create'))->assertOk();
        $create->assertSee('id="templateBody"', false);
        $create->assertSee('previewFrame', false);
        $create->assertSee('العناصر المتاحة');

        $template = PdfTemplate::create(['name' => 'ق', 'report_key' => 'debts', 'body' => '<p>نص</p>']);

        $edit = $this->actingAs($this->admin())->get(route('pdf-templates.edit', $template))->assertOk();
        $edit->assertSee('id="templateBody"', false);
        // الجسم داخل textarea فيظهر مُهرَّباً — وهو الصواب
        $edit->assertSee('&lt;p&gt;نص&lt;/p&gt;', false);
    }

    /** الجهات الحكومية وجرد الغرف والحجوزات قوالبها ثابتة. */
    public function test_a_locked_report_cannot_be_given_a_template(): void
    {
        foreach (['government', 'rooms_inventory', 'reservations'] as $locked) {
            $this->actingAs($this->admin())
                ->post(route('pdf-templates.store'), [
                    'name' => 'محاولة', 'report_key' => $locked, 'body' => '<p>مرحبا</p>',
                ])
                ->assertSessionHasErrors('report_key');
        }

        $this->assertSame(0, PdfTemplate::count());
        $this->assertTrue(PdfTemplate::choicesFor('government')->isEmpty());
    }

    /** قائمة القوالب تسمّي التقارير الثابتة صراحةً كي لا يبحث المدير عن سببها. */
    public function test_the_index_names_the_locked_reports(): void
    {
        $page = $this->actingAs($this->admin())->get(route('pdf-templates.index'))->assertOk();

        $page->assertSee('جرد غرف اليومية');
        $page->assertSee('تقرير الحجوزات');
        $page->assertSee('التصدير للجهات الحكومية');
    }

    /* ═══ الاستعمال عند التصدير ═══ */

    public function test_a_saved_template_takes_over_its_report_export(): void
    {
        $this->actingAs($this->admin())->post(route('pdf-templates.store'), [
            'name'       => 'ديون مختصرة',
            'report_key' => 'debts',
            'doc_title'  => 'كشف الديون المختصر',
            'body'       => '<p>علامة القالب المخصص</p>',
            'is_default' => 1,
            'is_active'  => 1,
        ])->assertRedirect();

        $template = PdfTemplate::firstOrFail();
        $this->assertTrue($template->is_default);

        $html = \App\Support\TemplatePdf::html($template, []);

        $this->assertStringContainsString('علامة القالب المخصص', $html);
        // وبترويسة الفندق نفسها كي لا تفقد المستندات هويتها
        $this->assertStringContainsString('كشف الديون المختصر', $html);
    }

    public function test_only_one_default_per_report(): void
    {
        $first  = PdfTemplate::create(['name' => 'أ', 'report_key' => 'debts', 'body' => 'أ', 'is_default' => true]);
        $second = PdfTemplate::create(['name' => 'ب', 'report_key' => 'debts', 'body' => 'ب']);

        $second->makeDefault();

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
    }

    public function test_the_export_uses_the_default_template_and_zero_forces_the_original(): void
    {
        PdfTemplate::create([
            'name' => 'ديون مخصص', 'report_key' => 'debts',
            'body' => '<p>بصمة القالب</p>', 'is_default' => true, 'is_active' => true,
        ]);

        $withTemplate = $this->actingAs($this->admin())->get(route('reports.debts.pdf'));
        $withTemplate->assertOk();

        // template=0 تعني تصميم النظام الأصلي صراحةً
        $original = $this->actingAs($this->admin())->get(route('reports.debts.pdf', ['template' => 0]));
        $original->assertOk();

        $this->assertNotSame(
            strlen($withTemplate->getContent()),
            strlen($original->getContent()),
            'القالب المخصص لم يغيّر ناتج التصدير'
        );
    }

    /** المعاينة تعمل وتنبّه إلى العناصر المجهولة بدل إخراج فراغ صامت. */
    public function test_the_preview_warns_about_unknown_placeholders(): void
    {
        $response = $this->actingAs($this->admin())->postJson(route('pdf-templates.preview'), [
            'report_key' => 'debts',
            'body'       => '<p>{{ اسم_الفندق }} و {{ حقل_مخترع }}</p>',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertStringContainsString('حقل_مخترع', $response->json('warning'));
    }

    /**
     * شعار الفندق مخزَّن كمسار ملف على الخادم — يفهمه dompdf ولا يفهمه
     * المتصفح، فكان يظهر مكسوراً في المعاينة. يُضمَّن الآن داخل الصفحة.
     */
    public function test_the_preview_embeds_the_logo_instead_of_a_server_path(): void
    {
        $logo = public_path('images/hotel-logo.png');
        if (!is_file($logo)) {
            @mkdir(dirname($logo), 0777, true);
            file_put_contents($logo, base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
            ));
        }

        $response = $this->actingAs($this->admin())->postJson(route('pdf-templates.preview'), [
            'report_key' => 'debts',
            'body'       => '<p>{{ اسم_الفندق }}</p>',
        ])->assertOk();

        $html = $response->json('html');

        $this->assertStringNotContainsString(base_path(), $html, 'تسرّب مسار الخادم إلى المعاينة');
        if (str_contains($html, '<img')) {
            $this->assertStringContainsString('src="data:image/', $html);
        }
    }

    public function test_the_preview_refuses_a_dangerous_body_in_arabic(): void
    {
        $this->actingAs($this->admin())->postJson(route('pdf-templates.preview'), [
            'report_key' => 'debts',
            'body'       => '<script>x</script>',
        ])->assertStatus(422)->assertJsonPath('success', false);
    }
}
