<?php

namespace App\Http\Controllers;

use App\Models\Expense;
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

        $expenses   = $query->paginate(25)->withQueryString();
        $categories = $this->categories;

        return view('expenses.index', compact('expenses', 'categories'));
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
        ]);

        $data['currency'] = 'YER';
        $data['paid_by']  = auth()->id();

        $activeShift = \App\Models\Shift::where('is_closed', false)->latest()->first();
        if ($activeShift) {
            $data['shift_id'] = $activeShift->id;
        }

        Expense::create($data);

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
        ]);

        $data['currency'] = 'YER';
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

        $query = Expense::with('paidBy')
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo);

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();

        $total = $expenses->sum('amount');

        $byCategory = $expenses->groupBy('category')->map(fn($g) => [
            'count' => $g->count(),
            'total' => $g->sum('amount'),
        ]);

        $categories = $this->categories;

        return view('expenses.report', compact(
            'expenses', 'total', 'byCategory', 'dateFrom', 'dateTo', 'categories'
        ));
    }
}
