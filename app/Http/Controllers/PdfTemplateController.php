<?php
namespace App\Http\Controllers;

use App\Models\PdfTemplate;
use App\Support\ReportRegistry;
use App\Support\TemplateData;
use App\Support\TemplatePdf;
use App\Support\TemplateRenderer;
use App\Support\TemplateSanitizer;
use Illuminate\Http\Request;

/**
 * تصميم قوالب تصدير PDF. مقصور على المدير: القالب يُطبع على مستندات
 * الفندق الرسمية، وتغييره ليس عملاً يومياً لموظف الاستقبال.
 */
class PdfTemplateController extends Controller
{
    public function index()
    {
        $templates = PdfTemplate::with('creator')->latest()->get()->groupBy('report_key');

        return view('pdf_templates.index', [
            'templates'  => $templates,
            'reports'    => ReportRegistry::customisable(),
            'lockedKeys' => collect(ReportRegistry::REPORTS)->filter(fn ($r) => !empty($r['locked'])),
        ]);
    }

    public function create(Request $request)
    {
        $reportKey = $request->input('report', array_key_first(ReportRegistry::customisable()));

        return view('pdf_templates.edit', [
            'template'     => new PdfTemplate([
                'report_key' => $reportKey,
                'body'       => $this->starterTemplate(),
            ]),
            'reports'      => ReportRegistry::customisable(),
            'placeholders' => $this->placeholdersFor($reportKey),
        ]);
    }

    public function edit(PdfTemplate $pdfTemplate)
    {
        return view('pdf_templates.edit', [
            'template'     => $pdfTemplate,
            'reports'      => ReportRegistry::customisable(),
            'placeholders' => $this->placeholdersFor($pdfTemplate->report_key),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($problems = TemplateSanitizer::problems($data['body'])) {
            return back()->withInput()->withErrors(['body' => implode(' • ', $problems)]);
        }

        $data['body']       = TemplateSanitizer::clean($data['body']);
        $data['created_by'] = $request->user()->id;

        $template = PdfTemplate::create($data);

        if ($request->boolean('is_default')) {
            $template->makeDefault();
        }

        return redirect()->route('pdf-templates.index')
            ->with('success', 'تم حفظ القالب «' . $template->name . '».');
    }

    public function update(Request $request, PdfTemplate $pdfTemplate)
    {
        $data = $this->validated($request);

        if ($problems = TemplateSanitizer::problems($data['body'])) {
            return back()->withInput()->withErrors(['body' => implode(' • ', $problems)]);
        }

        $data['body'] = TemplateSanitizer::clean($data['body']);
        $pdfTemplate->update($data);

        if ($request->boolean('is_default')) {
            $pdfTemplate->makeDefault();
        }

        return redirect()->route('pdf-templates.index')
            ->with('success', 'تم تحديث القالب «' . $pdfTemplate->name . '».');
    }

    public function destroy(PdfTemplate $pdfTemplate)
    {
        $name = $pdfTemplate->name;
        $pdfTemplate->delete();

        return back()->with('success', 'تم حذف القالب «' . $name . '».');
    }

    /** معاينة فورية أثناء التصميم — ببيانات التقرير الحقيقية. */
    public function preview(Request $request)
    {
        $request->validate([
            'report_key' => 'required|string',
            'body'       => 'required|string',
        ]);

        if (ReportRegistry::isLocked($request->input('report_key'))) {
            return response()->json([
                'success' => false,
                'message' => 'هذا التقرير قالبه ثابت ولا يقبل التخصيص.',
            ], 422);
        }

        if ($problems = TemplateSanitizer::problems($request->input('body'))) {
            return response()->json(['success' => false, 'message' => implode(' • ', $problems)], 422);
        }

        $template = new PdfTemplate([
            'report_key' => $request->input('report_key'),
            'doc_title'  => $request->input('doc_title'),
            'body'       => TemplateSanitizer::clean($request->input('body')),
        ]);

        $sample  = $this->sampleData($request->input('report_key'));
        $unknown = $this->unknownPlaceholders($template->body, $sample);

        return response()->json([
            'success' => true,
            'html'    => TemplatePdf::html($template, $sample, forBrowser: true),
            'warning' => $unknown
                ? 'عناصر غير معروفة لن تظهر في التصدير: ' . implode('، ', $unknown)
                : null,
        ]);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name'       => 'required|string|max:120',
            'report_key' => 'required|string|in:' . implode(',', array_keys(ReportRegistry::customisable())),
            'doc_title'  => 'nullable|string|max:150',
            'body'       => 'required|string',
            'is_active'  => 'nullable|boolean',
        ], [
            'report_key.in' => 'هذا التقرير قالبه ثابت ولا يقبل التخصيص.',
            'body.required' => 'كود القالب مطلوب — الصق تصميم القالب في المحرِّر.',
        ]);
    }

    /** العناصر المتاحة لتقرير معيّن، مأخوذة من بياناته الحقيقية. */
    private function placeholdersFor(string $reportKey): array
    {
        return TemplateData::describe($this->sampleData($reportKey));
    }

    /**
     * بيانات المعاينة. نستعمل عيّنة ثابتة لا استدعاءً للتقرير نفسه، فبعض
     * التقارير تحتاج مُدخلات (تواريخ، موظف) لا تتوفر في شاشة التصميم.
     */
    private function sampleData(string $reportKey): array
    {
        $common = TemplateData::common(ReportRegistry::label($reportKey));

        return $common + [
            'الصفوف' => [
                ['الغرفة' => '101', 'النزيل' => 'أحمد محمد', 'المبلغ' => 25000, 'التاريخ' => now()->toDateString()],
                ['الغرفة' => '203', 'النزيل' => 'سالم علي',  'المبلغ' => 40000, 'التاريخ' => now()->toDateString()],
            ],
            'الإجمالي'   => 65000,
            'عدد_الصفوف' => 2,
            'من_تاريخ'   => now()->startOfMonth()->format('d/m/Y'),
            'إلى_تاريخ'  => now()->format('d/m/Y'),
        ];
    }

    private function unknownPlaceholders(string $body, array $data): array
    {
        return array_values(array_diff(TemplateRenderer::placeholdersIn($body), array_keys($data), ['ترتيب', 'index']));
    }

    private function starterTemplate(): string
    {
        return <<<'HTML'
<h2 style="text-align:center;margin-bottom:12px;">{{ عنوان_التقرير }}</h2>

<p style="text-align:center;color:#6b7280;">
    من {{ من_تاريخ }} إلى {{ إلى_تاريخ }}
</p>

<table>
    <thead>
        <tr>
            <th style="width:60px;">م</th>
            <th>الغرفة</th>
            <th>النزيل</th>
            <th>التاريخ</th>
            <th style="width:120px;">المبلغ</th>
        </tr>
    </thead>
    <tbody>
        {% تكرار الصفوف %}
        <tr>
            <td>{{ ترتيب }}</td>
            <td>{{ الغرفة }}</td>
            <td>{{ النزيل }}</td>
            <td>{{ التاريخ | تاريخ }}</td>
            <td>{{ المبلغ | رقم }} {{ العملة }}</td>
        </tr>
        {% نهاية %}
    </tbody>
    <tfoot>
        <tr>
            <th colspan="4">الإجمالي</th>
            <th>{{ الإجمالي | رقم }} {{ العملة }}</th>
        </tr>
    </tfoot>
</table>

<p style="margin-top:20px;font-size:10px;color:#9ca3af;">
    أُصدر بواسطة {{ المستخدم }} بتاريخ {{ التاريخ_والوقت }}
</p>
HTML;
    }
}
