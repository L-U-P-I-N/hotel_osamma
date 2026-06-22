<?php
namespace App\Http\Controllers;

use App\Models\CashSettlement;
use App\Models\CashWithdrawal;
use App\Models\Expense;
use App\Models\Shift;
use App\Models\User;
use App\Services\CashSettlementService;
use Illuminate\Http\Request;

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

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->input('date_to'));
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('recipient_name', 'like', '%' . $request->input('search') . '%')
                  ->orWhere('description', 'like', '%' . $request->input('search') . '%');
            });
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }
        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->input('shift_id'));
        }

        $expenses   = $query->paginate(25)->withQueryString();
        $categories = $this->categories;

        $user = auth()->user();
        $shiftsQuery = Shift::with('user')->orderBy('shift_date', 'desc')->orderBy('id', 'desc')->limit(60);
        if (!$user->isAdmin()) {
            $shiftsQuery->where('user_id', $user->id);
        }
        $availableShifts = $shiftsQuery->get();

        return view('expenses.index', compact('expenses', 'categories', 'availableShifts'));
    }

    public function create()
    {
        $categories = $this->categories;
        return view('expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'category'       => 'required|in:maintenance,electricity,salary,cleaning,food,other',
            'recipient_name' => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'expense_date'   => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,later',
        ]);

        $data['currency']       = 'YER';
        $data['paid_by']        = auth()->id();
        $data['payment_method'] = $request->input('payment_method', 'cash');

        $activeShift = Shift::where('is_closed', false)->where('user_id', auth()->id())->latest()->first();
        if ($activeShift) {
            $data['shift_id'] = $activeShift->id;
        }

        $expense = Expense::create($data);

        if ($expense->isPaidFromCash()) {
            $this->syncWithdrawal($expense, $activeShift);
        }

        return redirect()->route('expenses.index')->with('success', 'تم تسجيل المصروف بنجاح');
    }

    public function edit(Expense $expense)
    {
        $categories = $this->categories;
        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'category'       => 'required|in:maintenance,electricity,salary,cleaning,food,other',
            'recipient_name' => 'nullable|string|max:255',
            'description'    => 'nullable|string',
            'expense_date'   => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,later',
        ]);

        $data['currency'] = 'YER';
        $expense->update($data);
        $expense->refresh();

        if ($expense->isPaidFromCash()) {
            $activeShift = Shift::where('is_closed', false)->where('user_id', auth()->id())->latest()->first();
            $this->syncWithdrawal($expense, $activeShift);
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
        $deferredExpenses = Expense::with('paidBy')
            ->where('payment_method', 'later')
            ->whereNull('settled_at')
            ->orderBy('expense_date', 'asc')
            ->get();

        $recentlySettled = Expense::with(['paidBy', 'settledBy'])
            ->where('payment_method', 'later')
            ->whereNotNull('settled_at')
            ->where('settled_at', '>=', now()->subDays(30))
            ->orderBy('settled_at', 'desc')
            ->get();

        $totalDeferred = $deferredExpenses->sum('amount');
        $totalSettled  = Expense::where('payment_method', 'later')
            ->whereNotNull('settled_at')
            ->sum('amount');

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

        \App\Services\AuditLogService::log('expense_settled', $expense, null, ['amount' => $expense->amount]);

        return back()->with('success', 'تم تسوية المصروف بنجاح');
    }

    public function report(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());

        $query = Expense::with('paidBy')
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo);

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();
        $total    = $expenses->sum('amount');

        $byCategory = $expenses->groupBy('category')->map(fn($g) => [
            'count' => $g->count(),
            'total' => $g->sum('amount'),
        ]);

        $byMethod = $expenses->groupBy('payment_method')->map(fn($g) => [
            'count' => $g->count(),
            'total' => $g->sum('amount'),
        ]);

        $categories = $this->categories;

        return view('expenses.report', compact(
            'expenses', 'total', 'byCategory', 'byMethod',
            'dateFrom', 'dateTo', 'categories'
        ));
    }

    // ── Helpers ──────────────────────────────────────────────────────────

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
