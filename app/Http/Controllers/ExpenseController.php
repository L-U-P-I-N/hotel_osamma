<?php
namespace App\Http\Controllers;

use App\Exports\ExpenseExport;
use App\Models\CashSettlement;
use App\Models\CashWithdrawal;
use App\Models\Expense;
use App\Models\Shift;
use App\Models\User;
use App\Services\CashSettlementService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExpenseController extends Controller
{
    private array $categories = [
        'maintenance' => 'صيانة',
        'electricity' => 'كهرباء/مياه',
        'salary'      => 'رواتب',
        'cleaning'    => 'نظافة',
        'food'        => 'طعام وشراب',
        'other'       => 'أخرى',
    ];

    public function index(Request $request)
    {
        $query = Expense::with('paidBy')->orderBy('expense_date', 'desc')->orderBy('id', 'desc');
        $statsQuery = Expense::query();

        // غير الأدمن يرى مصروفاته فقط؛ الأدمن يرى مصروفات كل المستخدمين
        $this->scopeOwn($query);
        $this->scopeOwn($statsQuery);

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
            $statsQuery->where('category', $request->input('category'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->input('date_from'));
            $statsQuery->whereDate('expense_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->input('date_to'));
            $statsQuery->whereDate('expense_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('recipient_name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('description', 'like', '%' . $request->input('search') . '%');
            });
            $statsQuery->where(function ($q) use ($request) {
                $q->where('recipient_name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('description', 'like', '%' . $request->input('search') . '%');
            });
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
            $statsQuery->where('payment_method', $request->input('payment_method'));
        }
        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->input('shift_id'));
            $statsQuery->where('shift_id', $request->input('shift_id'));
        }

        $expenses   = $query->paginate(25)->withQueryString();
        $categories = $this->categories;

        $allExpenses = $statsQuery->get();
        $stats = [
            'total'   => $allExpenses->sum('amount'),
            'count'   => $allExpenses->count(),
            'average' => $allExpenses->count() > 0 ? $allExpenses->sum('amount') / $allExpenses->count() : 0,
            'min'     => $allExpenses->count() > 0 ? $allExpenses->min('amount') : 0,
            'max'     => $allExpenses->count() > 0 ? $allExpenses->max('amount') : 0,
        ];

        $byCategory = $allExpenses->groupBy('category')->map(function($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ];
        });

        // ملخص حسب طريقة الدفع (مدموج من صفحة التقرير السابقة)
        $byMethod = $allExpenses->groupBy('payment_method')->map(fn($group) => [
            'count' => $group->count(),
            'total' => $group->sum('amount'),
        ]);

        $user = auth()->user();
        $shiftsQuery = Shift::with('user')->orderBy('shift_date', 'desc')->orderBy('id', 'desc')->limit(60);
        if (!$user->isAdmin()) {
            $shiftsQuery->where('user_id', $user->id);
        }
        $availableShifts = $shiftsQuery->get();

        $activeShift = Shift::where('is_closed', false)->where('user_id', auth()->id())->latest()->first();

        return view('expenses.index', compact('expenses', 'categories', 'availableShifts', 'stats', 'byCategory', 'byMethod', 'activeShift'));
    }

    public function create()
    {
        $categories = $this->categories;
        $employees  = \App\Models\Employee::where('is_active', true)->orderBy('name')->get();
        return view('expenses.create', compact('categories', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'category'       => 'required|in:maintenance,electricity,salary,cleaning,food,other',
            'recipient_name' => 'required|string|max:255',
            'employee_id'    => 'nullable|exists:employees,id',
            'description'    => 'nullable|string',
            'expense_date'   => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,later',
        ]);

        $data['currency']       = 'YER';
        $data['paid_by']        = auth()->id();
        $data['payment_method'] = $request->input('payment_method', 'cash');

        $targetShift = $this->resolveShiftForExpense($data['expense_date']);
        if ($targetShift) {
            $data['shift_id'] = $targetShift->id;
        }

        $expense = Expense::create($data);

        if ($expense->isPaidFromCash()) {
            $this->syncWithdrawal($expense, $targetShift);
        }

        return redirect()->route('expenses.index')->with('success', 'تم تسجيل المصروف بنجاح');
    }

    /**
     * الوردية التي يجب نسب هذا المصروف إليها: نُفضّل وردية المستخدم التي يخصّها
     * تاريخ المصروف فعلياً (ولو كانت مقفلة) — فمصروف مسجَّل بتاريخ سابق (تصحيح
     * متأخر) يُنسَب ليوم حدوثه الفعلي، لا وردية اليوم المفتوحة حالياً. هذا يضمن
     * ظهور المصروف عند مراجعة/إعادة فتح وردية ذلك اليوم لاحقاً بدل ضياعه في
     * وردية لاحقة لا علاقة لها بتاريخه. نسقط لوردية المستخدم المفتوحة حالياً
     * فقط إن لم توجد له وردية بتاريخ المصروف نفسه.
     */
    private function resolveShiftForExpense(string $expenseDate): ?Shift
    {
        $user = auth()->user();

        return Shift::where('user_id', $user->id)
                ->whereDate('shift_date', $expenseDate)
                ->latest()
                ->first()
            ?? Shift::where('is_closed', false)->where('user_id', $user->id)->latest()->first();
    }

    public function edit(Expense $expense)
    {
        $categories = $this->categories;
        $employees  = \App\Models\Employee::where('is_active', true)->orderBy('name')->get();
        return view('expenses.edit', compact('expense', 'categories', 'employees'));
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'category'       => 'required|in:maintenance,electricity,salary,cleaning,food,other',
            'recipient_name' => 'required|string|max:255',
            'employee_id'    => 'nullable|exists:employees,id',
            'description'    => 'nullable|string',
            'expense_date'   => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,later',
        ]);

        $data['currency'] = 'YER';
        // إعادة نسب المصروف لوردية تاريخه الجديد إن تغيّر التاريخ عند التعديل —
        // وإلا يبقى منسوباً لوردية تاريخه القديم رغم تعديل التاريخ.
        $targetShift = $this->resolveShiftForExpense($data['expense_date']);
        $data['shift_id'] = $targetShift?->id;
        $expense->update($data);
        $expense->refresh();

        if ($expense->isPaidFromCash()) {
            $this->syncWithdrawal($expense, $targetShift);
        } else {
            // طريقة الدفع تغيّرت → احذف السحب المرتبط (Observer يعيد الحساب تلقائياً)
            $expense->cashWithdrawal()?->delete();
            $this->recomputeSettlement($expense);
        }

        return redirect()->route('expenses.index')->with('success', 'تم تحديث المصروف بنجاح');
    }

    public function destroy(Expense $expense)
    {
        $expense->cashWithdrawal()?->delete();
        $this->recomputeSettlement($expense);
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'تم حذف المصروف بنجاح');
    }

    public function deferred(Request $request)
    {
        $deferredQuery = Expense::with('paidBy')
            ->where('payment_method', 'later')
            ->whereNull('settled_at')
            ->orderBy('expense_date', 'asc');
        $this->scopeOwn($deferredQuery);
        $deferredExpenses = $deferredQuery->get();

        $settledQuery = Expense::with(['paidBy', 'settledBy'])
            ->where('payment_method', 'later')
            ->whereNotNull('settled_at')
            ->where('settled_at', '>=', now()->subDays(30))
            ->orderBy('settled_at', 'desc');
        $this->scopeOwn($settledQuery);
        $recentlySettled = $settledQuery->get();

        $totalSettledQuery = Expense::where('payment_method', 'later')->whereNotNull('settled_at');
        $this->scopeOwn($totalSettledQuery);

        $totalDeferred = $deferredExpenses->sum('amount');
        $totalSettled  = $totalSettledQuery->sum('amount');

        return view('expenses.deferred', compact('deferredExpenses', 'recentlySettled', 'totalDeferred', 'totalSettled'));
    }

    public function settle(Expense $expense)
    {
        if ($expense->settled_at) {
            return back()->withErrors(['error' => 'هذا المصروف مسوّى بالفعل']);
        }

        $expense->update([
            'settled_at' => now(),
            'settled_by' => auth()->id(),
        ]);

        // 'action' عمود enum ثابت القيم في audit_logs — لا يقبل 'expense_settled'
        \App\Services\AuditLogService::log('update', $expense, null, ['amount' => $expense->amount, 'settled_at' => now()->toDateTimeString()]);

        return back()->with('success', 'تم تسوية المصروف بنجاح');
    }

    public function exportExcel(Request $request)
    {
        $export = new ExpenseExport(
            $request->input('date_from'),
            $request->input('date_to'),
            $request->input('category'),
            $request->input('payment_method'),
            $request->input('search'),
            $request->input('shift_id'),
            auth()->user()->isAdmin() ? null : auth()->id(),
        );
        $filename = 'المصروفات_' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download($export, $filename);
    }

    public function exportPdf(Request $request)
    {
        $query = Expense::with('paidBy')->orderBy('expense_date', 'desc')->orderBy('id', 'desc');
        $this->scopeOwn($query);

        $dateFrom      = $request->input('date_from');
        $dateTo        = $request->input('date_to');
        $category      = $request->input('category');
        $paymentMethod = $request->input('payment_method');
        $search        = $request->input('search');
        $shiftId       = $request->input('shift_id');

        if ($dateFrom)      { $query->whereDate('expense_date', '>=', $dateFrom); }
        if ($dateTo)        { $query->whereDate('expense_date', '<=', $dateTo); }
        if ($category)      { $query->where('category', $category); }
        if ($paymentMethod) { $query->where('payment_method', $paymentMethod); }
        if ($shiftId)       { $query->where('shift_id', $shiftId); }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient_name', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $expenses = $query->get();

        $pdf = pdf_load_view('expenses.expense_pdf', compact('expenses', 'dateFrom', 'dateTo'));

        $dompdf = $pdf->getDomPDF();
        $opts   = $dompdf->getOptions();
        $opts->setFontDir(storage_path('fonts'));
        $opts->setFontCache(storage_path('fonts'));
        $dompdf->setOptions($opts);

        $pdf->setPaper('a4', 'portrait');
        return $pdf->download('المصروفات_' . now()->format('Y-m-d') . '.pdf');
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * يقصر الاستعلام على مصروفات المستخدم الحالي فقط، إلا إن كان أدمن
     * (الأدمن يرى مصروفات جميع المستخدمين).
     */
    private function scopeOwn($query): void
    {
        $user = auth()->user();
        if ($user && !$user->isAdmin()) {
            $query->where('paid_by', $user->id);
        }
    }

    private function syncWithdrawal(Expense $expense, ?Shift $shift): void
    {
        $settlement = $this->getOrCreateSettlement(auth()->user());
        if (!$settlement) {
            return;
        }

        $withdrawal = CashWithdrawal::where('expense_id', $expense->id)->first();

        $payload = [
            'cash_settlement_id' => $settlement->id,
            'shift_id'           => $shift?->id,
            'expense_id'         => $expense->id,
            'amount'             => $expense->amount,
            'currency'           => $expense->currency,
            'withdrawal_date'    => now(),
            'withdrawn_by_name'  => $expense->recipient_name ?? '—',
            'handed_by_name'     => auth()->user()->name,
            'notes'              => $expense->description ?? \App\Models\Expense::categoryLabel($expense->category),
            'withdrawal_type'    => 'expense',
        ];

        if ($withdrawal) {
            $withdrawal->update($payload);
        } else {
            CashWithdrawal::create($payload);
        }

        app(CashSettlementService::class)->computeTotals($settlement);
    }

    private function getOrCreateSettlement(\App\Models\User $user): ?CashSettlement
    {
        return CashSettlement::firstOrCreate(
            ['user_id' => $user->id, 'shift_date' => today()],
            ['status' => 'open', 'total_received' => 0, 'total_withdrawals' => 0, 'net_balance' => 0]
        );
    }

    private function recomputeSettlement(Expense $expense): void
    {
        $settlement = CashSettlement::where('user_id', auth()->id())
            ->where('shift_date', $expense->expense_date)
            ->first();

        if ($settlement) {
            app(CashSettlementService::class)->computeTotals($settlement);
        }
    }
}
