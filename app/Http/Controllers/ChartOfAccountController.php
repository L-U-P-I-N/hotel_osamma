<?php

namespace App\Http\Controllers;

use App\Http\Resources\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use App\Services\COAService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        return view('accounting.chart-of-accounts', [
            'tree'        => $this->coa->buildTree($filters),
            'filters'     => $filters,
            'types'       => ChartOfAccount::TYPES,
            'departments' => ChartOfAccount::DEPARTMENTS,
            'totals'      => [
                'all'     => ChartOfAccount::count(),
                'posting' => ChartOfAccount::postingAccounts()->count(),
            ],
        ]);
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
