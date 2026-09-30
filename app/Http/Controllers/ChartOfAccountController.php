<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChartOfAccountRequest;
use App\Http\Requests\UpdateChartOfAccountRequest;
use App\Http\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use App\Services\COAService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * واجهة شجرة الحسابات: صفحة العرض + نقاط JSON.
 * كل المنطق في COAService — هذا المتحكم يترجم الطلب إلى استدعاء ويعيد التمثيل.
 */
class ChartOfAccountController extends Controller
{
    public function __construct(private readonly COAService $coa) {}

    /** صفحة الشجرة القابلة للطي */
    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $canManage = $request->user()?->can('accounts.manage') ?? false;

        return view('accounting.chart-of-accounts', [
            // الأرصدة تُحمَّل مع الشجرة: المحاسب يقرأ الرقم قبل الاسم
            'tree'        => $this->coa->buildTree($filters, true),
            'filters'     => $filters,
            'types'       => ChartOfAccount::TYPES,
            'departments' => ChartOfAccount::DEPARTMENTS,
            'canManage'   => $canManage,
            // النموذج يُملأ من الخادم لا من جافاسكربت: الشجرة روابط عادية،
            // فتعمل الصفحة كاملةً حتى لو تعطّل السكربت، ويبقى الرجوع للخلف سليماً
            'editing'     => $canManage ? $this->accountBeingEdited($request) : null,
            'creating'    => $canManage ? $this->accountBeingCreated($request) : null,
            'parents'     => $canManage ? $this->selectableParents() : collect(),
            'lockedCodes' => $canManage ? $this->coa->hardcodedPostingCodes() : [],
            'totals'      => [
                'all'     => ChartOfAccount::count(),
                'posting' => ChartOfAccount::postingAccounts()->count(),
            ],
        ]);
    }

    /** إنشاء حساب فرعي جديد تحت أب مختار */
    public function store(StoreChartOfAccountRequest $request): RedirectResponse
    {
        $data   = $request->validated();
        $parent = ChartOfAccount::where('code', $data['parent_code'])->firstOrFail();

        $account = DB::transaction(static function () use ($data, $parent): ChartOfAccount {
            $level = $parent->level + 1;

            $account = ChartOfAccount::create([
                'code'        => $data['code'],
                'parent_code' => $parent->code,
                'name_ar'     => $data['name_ar'],
                // الاسم الإنجليزي اختياري: يُستعمل في تصدير JSON فقط، وتركه
                // فارغاً يجعل الحساب بلا اسم هناك، فيُنسخ العربي بدلاً منه
                'name_en'     => ($data['name_en'] ?? null) ?: $data['name_ar'],
                // النوع والطبيعة الفرعية من الأب: الفرع لا يخالف أصله، وإلا
                // جمعت التقارير أصلاً تحت خصوم
                'type'        => $parent->type,
                'subtype'     => $parent->subtype,
                'department'  => $data['department'] ?? $parent->department,
                'level'       => $level,
                // الترحيل للأوراق في المستوى الثالث فصاعداً — والموديل يفرضها
                'is_posting'  => $level >= 3,
                'is_active'   => true,
                'is_system'   => false,
                'notes'       => $data['notes'] ?? null,
            ]);

            // الأب صار تجميعياً بمجرد أن صار له فرع، وإلا حُسب رصيده مع فروعه
            if ($parent->is_posting) {
                $parent->update(['is_posting' => false]);
            }

            return $account;
        });

        return redirect()
            ->route('coa.index', ['edit' => $account->code])
            ->with('success', "أُضيف الحساب {$account->code} — {$account->name_ar}");
    }

    /** تعديل ما يجوز تعديله: الاسم، القسم، الملاحظة، الإيقاف */
    public function update(UpdateChartOfAccountRequest $request, string $code): RedirectResponse
    {
        $account = ChartOfAccount::where('code', $code)->firstOrFail();

        // الحسابات الأساسية التجميعية تُقرأ ولا تُكتب — تُحرَس هنا لا في الواجهة
        // وحدها، فتعطيل الحقل في الصفحة لا يمنع طلباً يصل من خارجها
        if (($locked = $account->editBlocker()) !== null) {
            return back()->with('error', $locked);
        }

        $data    = $request->validated();
        // النموذج يعرض «إيقاف الحساب» كما في دفاتر الحسابات المتعارف عليها
        $active  = !$request->boolean('suspended');

        if (!$active && ($blocker = $this->deactivationBlocker($account)) !== null) {
            return back()->withInput()->with('error', $blocker);
        }

        $account->update([
            'name_ar'    => $data['name_ar'],
            'name_en'    => ($data['name_en'] ?? null) ?: $data['name_ar'],
            'department' => $data['department'] ?? null,
            'notes'      => $data['notes'] ?? null,
            'is_active'  => $active,
        ]);

        return redirect()
            ->route('coa.index', ['edit' => $account->code])
            ->with('success', "حُفظ الحساب {$account->code}");
    }

    /** حذف حساب أضافه المستخدم ولم يُستعمل — وما عداه يُوقَف لا يُحذف */
    public function destroy(Request $request, string $code): RedirectResponse
    {
        abort_unless($request->user()?->can('accounts.manage'), 403);

        $account = ChartOfAccount::where('code', $code)->firstOrFail();

        if (($blocker = $account->deletionBlocker()) !== null) {
            return back()->with('error', $blocker);
        }

        $parent = $account->parent;
        $label  = $account->code . ' — ' . $account->name_ar;

        DB::transaction(static function () use ($account, $parent): void {
            $account->delete();

            // آخر فرع رحل، فيعود الأب ورقةً قابلة للترحيل كما كان
            if ($parent !== null && $parent->level >= 3 && !$parent->children()->exists()) {
                $parent->update(['is_posting' => true]);
            }
        });

        return redirect()
            ->route('coa.index', $parent ? ['edit' => $parent->code] : [])
            ->with('success', "حُذف الحساب {$label}");
    }

    /**
     * سبب منع الإيقاف بالعربية، أو null. الأكواد المثبّتة في شيفرة الترحيل لا
     * تُوقَف: JournalService يرفض الحساب الموقوف، فالعطل لا يظهر لحظة الإيقاف
     * بل لحظة تسجيل دفعةٍ لنزيل حقيقي بعد أيام.
     */
    private function deactivationBlocker(ChartOfAccount $account): ?string
    {
        if (in_array($account->code, $this->coa->hardcodedPostingCodes(), true)) {
            return 'الحساب ' . $account->code . ' مستعمل في ترحيل العمليات اليومية '
                 . '(الدفعات أو المصروفات أو الرواتب) فلا يمكن إيقافه — إيقافه يُعطّل تسجيلها.';
        }

        if ($account->children()->where('is_active', true)->exists()) {
            return 'للحساب فروع نشطة — أوقِفها أولاً.';
        }

        // إيقاف حساب وعاءٍ نشط يُعطّل قبضه وصرفه دون أن يظهر ذلك في صفحته
        $vessel = $account->paymentAccount;
        if ($vessel !== null && $vessel->is_active) {
            return 'هذا حساب الوعاء المالي «' . $vessel->name . '» وهو نشط — '
                 . 'عطّله من صفحة الصناديق والبنوك ليُوقَف حسابه معه.';
        }

        return null;
    }

    /** الحساب المطلوب تعديله من رابط الشجرة */
    private function accountBeingEdited(Request $request): ?ChartOfAccount
    {
        $code = $request->query('edit');

        return $code ? ChartOfAccount::where('code', $code)->first() : null;
    }

    /**
     * أب الحساب الجديد. عند الضغط على «جديد» من عقدة في الشجرة يأتي كودها
     * فيُقترح أول رقم حرّ تحته، وإلا فُتح النموذج فارغاً ليختار المستخدم أباً.
     */
    private function accountBeingCreated(Request $request): ?array
    {
        if (!$request->has('new')) {
            return null;
        }

        $parent = ChartOfAccount::where('code', $request->query('parent'))->first();

        return [
            'parent'        => $parent,
            'suggested'     => $parent?->nextChildCode(),
            'parent_locked' => $parent !== null,
        ];
    }

    /** الحسابات التي تصلح آباءً: دون المستوى الرابع وبلا قيود مرحَّلة عليها */
    private function selectableParents()
    {
        return ChartOfAccount::query()
            ->where('level', '<', 4)
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->reject(static fn (ChartOfAccount $a) => $a->hasJournalLines())
            ->values();
    }

    /** الشجرة كاملةً بصيغة JSON متداخلة */
    public function tree(Request $request): JsonResponse
    {
        return response()->json([
            'currency' => config('hotel.base_currency'),
            'data'     => $this->coa->buildTree($this->filters($request)),
        ]);
    }

    /** حساب واحد مع أبنائه — عبر مورد الـ API */
    public function show(string $code): JsonResponse
    {
        $account = ChartOfAccount::with('childrenRecursive')
            ->where('code', $code)
            ->firstOrFail();

        return response()->json([
            'data' => new ChartOfAccountResource($account),
            'path' => $this->coa->getAccountPath($code),
        ]);
    }

    /** قائمة الحسابات القابلة للترحيل — تُغذّي قوائم اختيار القيود */
    public function postingAccounts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'       => 'nullable|in:' . implode(',', ChartOfAccount::TYPES),
            'department' => 'nullable|in:' . implode(',', ChartOfAccount::DEPARTMENTS),
        ]);

        $accounts = $this->coa->getPostingAccounts(
            $validated['type'] ?? null,
            $validated['department'] ?? null
        );

        return response()->json([
            'data' => $accounts->map(static fn (ChartOfAccount $a) => [
                'code'           => $a->code,
                'name_en'        => $a->name_en,
                'name_ar'        => $a->name_ar,
                'type'           => $a->type,
                'department'     => $a->department,
                'normal_balance' => $a->normal_balance,
            ])->values(),
        ]);
    }

    /** تحقق من توازن قيد قبل ترحيله */
    public function validateEntry(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lines'                => 'required|array|min:1',
            'lines.*.account_code' => 'required|string|max:20',
            'lines.*.debit'        => 'nullable|numeric',
            'lines.*.credit'       => 'nullable|numeric',
        ], [
            'lines.required'                => 'سطور القيد مطلوبة',
            'lines.*.account_code.required' => 'كود الحساب مطلوب في كل سطر',
        ]);

        $result = $this->coa->validateJournalEntry($validated['lines']);

        return response()->json($result, $result['valid'] ? 200 : 422);
    }

    /** فحص سلامة الشجرة — يُستخدم في المراجعة الدورية */
    public function integrity(): JsonResponse
    {
        $issues = $this->coa->findIntegrityIssues();

        return response()->json([
            'healthy' => $issues === [],
            'issues'  => $issues,
        ]);
    }

    /** @return array<string,mixed> */
    private function filters(Request $request): array
    {
        $validated = $request->validate([
            'type'         => 'nullable|in:' . implode(',', ChartOfAccount::TYPES),
            'department'   => 'nullable|in:' . implode(',', ChartOfAccount::DEPARTMENTS),
            'posting_only' => 'nullable|boolean',
            'only_active'  => 'nullable|boolean',
        ]);

        return [
            'type'         => $validated['type'] ?? null,
            'department'   => $validated['department'] ?? null,
            'posting_only' => $request->boolean('posting_only'),
            'only_active'  => !$request->has('only_active') || $request->boolean('only_active'),
        ];
    }
}
