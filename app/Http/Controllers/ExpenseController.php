<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Supplier;
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
        $query = Expense::with('supplier', 'paidBy')->orderBy('expense_date', 'desc')->orderBy('id', 'desc');

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('currency')) {
            $query->where('currency', $request->input('currency'));
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('expense_date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('expense_date', '<=', $request->input('date_to'));
        }

        $expenses  = $query->paginate(25)->withQueryString();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();
        $categories = $this->categories;

        return view('expenses.index', compact('expenses', 'suppliers', 'categories'));
    }

    public function create()
    {
        $suppliers  = Supplier::where('is_active', true)->orderBy('name')->get();
        $categories = $this->categories;
        return view('expenses.create', compact('suppliers', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'currency'     => 'required|in:YER,SAR,USD',
            'category'     => 'required|in:maintenance,electricity,salary,cleaning,food,other',
            'supplier_id'  => 'nullable|exists:suppliers,id',
            'description'  => 'nullable|string',
            'expense_date' => 'required|date',
        ]);

        $data['paid_by'] = auth()->id();

        // Try to attach to an active shift if none provided
        $activeShift = \App\Models\Shift::where('is_closed', false)->latest()->first();
        if ($activeShift) {
            $data['shift_id'] = $activeShift->id;
        }

        Expense::create($data);

        return redirect()->route('expenses.index')->with('success', 'تم تسجيل المصروف بنجاح');
    }

    public function edit(Expense $expense)
    {
        $suppliers  = Supplier::where('is_active', true)->orderBy('name')->get();
        $categories = $this->categories;
        return view('expenses.edit', compact('expense', 'suppliers', 'categories'));
    }

    public function update(Request $request, Expense $expense)
    {
        $data = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'currency'     => 'required|in:YER,SAR,USD',
            'category'     => 'required|in:maintenance,electricity,salary,cleaning,food,other',
            'supplier_id'  => 'nullable|exists:suppliers,id',
            'description'  => 'nullable|string',
            'expense_date' => 'required|date',
        ]);

        $expense->update($data);

        return redirect()->route('expenses.index')->with('success', 'تم تحديث المصروف بنجاح');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('success', 'تم حذف المصروف بنجاح');
    }

    public function report(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());

        $query = Expense::with('supplier')
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo);

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }
        if ($request->filled('currency')) {
            $query->where('currency', $request->input('currency'));
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();

        // Totals per currency
        $totals = $expenses->groupBy('currency')->map(fn($g) => $g->sum('amount'));

        // By category
        $byCategory = $expenses->groupBy('category')->map(fn($g) => [
            'count'  => $g->count(),
            'totals' => $g->groupBy('currency')->map(fn($c) => $c->sum('amount')),
        ]);

        $suppliers  = Supplier::where('is_active', true)->orderBy('name')->get();
        $categories = $this->categories;

        return view('expenses.report', compact(
            'expenses', 'totals', 'byCategory', 'dateFrom', 'dateTo', 'suppliers', 'categories'
        ));
    }
}
