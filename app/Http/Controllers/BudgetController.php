<?php
namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Salary;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $year    = (int) $request->input('year', now()->year);
        $budgets = Budget::where('year', $year)->orderBy('month')->get()->keyBy('month');

        // Build 12-month comparison
        $months = collect();
        for ($m = 1; $m <= 12; $m++) {
            $actualRevenue = Payment::whereYear('payment_date', $year)
                ->whereMonth('payment_date', $m)
                ->where('currency', 'YER')
                ->sum('amount');
            $actualExpense = Expense::whereYear('expense_date', $year)
                ->whereMonth('expense_date', $m)
                ->where('currency', 'YER')
                ->sum('amount');

            $budget = $budgets->get($m);
            $months->push([
                'month'          => $m,
                'month_name'     => Salary::monthName($m),
                'revenue_target' => $budget?->revenue_target ?? 0,
                'expense_limit'  => $budget?->expense_limit ?? 0,
                'actual_revenue' => (float) $actualRevenue,
                'actual_expense' => (float) $actualExpense,
                'net_budget'     => ($budget?->revenue_target ?? 0) - ($budget?->expense_limit ?? 0),
                'net_actual'     => (float) $actualRevenue - (float) $actualExpense,
                'budget_id'      => $budget?->id,
            ]);
        }

        $years = range(now()->year - 2, now()->year + 1);

        return view('budgets.index', compact('months', 'year', 'years', 'budgets'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'year'           => 'required|integer|min:2020|max:2100',
            'month'          => 'required|integer|min:1|max:12',
            'revenue_target' => 'required|numeric|min:0',
            'expense_limit'  => 'required|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ], [
            'revenue_target.required' => 'الهدف الإيرادي مطلوب',
            'expense_limit.required'  => 'سقف المصروفات مطلوب',
        ]);

        $validated['created_by'] = auth()->id();

        Budget::updateOrCreate(
            ['year' => $validated['year'], 'month' => $validated['month']],
            $validated
        );

        return back()->with('success', 'تم حفظ الميزانية بنجاح');
    }

    public function destroy(Budget $budget)
    {
        $budget->delete();
        return back()->with('success', 'تم حذف الميزانية');
    }
}
