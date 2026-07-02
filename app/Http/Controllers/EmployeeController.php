<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::withTrashed(false)
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->paginate(20);

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'national_id' => 'nullable|string|max:50|unique:employees,national_id',
            'position'    => 'required|string|max:255',
            'base_salary' => 'required|numeric|min:0',
            'phone'       => 'nullable|string|max:30',
            'hire_date'   => 'required|date',
            'is_active'   => 'boolean',
            'notes'       => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        Employee::create($data);

        return redirect()->route('employees.index')->with('success', 'تم إضافة الموظف بنجاح');
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'national_id' => 'nullable|string|max:50|unique:employees,national_id,' . $employee->id,
            'position'    => 'required|string|max:255',
            'base_salary' => 'required|numeric|min:0',
            'phone'       => 'nullable|string|max:30',
            'hire_date'   => 'required|date',
            'is_active'   => 'boolean',
            'notes'       => 'nullable|string',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $employee->update($data);

        return redirect()->route('employees.index')->with('success', 'تم تحديث بيانات الموظف بنجاح');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return redirect()->route('employees.index')->with('success', 'تم حذف الموظف بنجاح');
    }

    /**
     * كشف مسحوبات الموظف: كل مبلغ صُرف له من الصندوق (التاريخ والوقت والمبلغ)،
     * مع إجمالي مسحوبات الشهر المحدد وخصمها من راتبه وعرض المتبقي منه.
     */
    public function withdrawals(Request $request, Employee $employee)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $withdrawals = $employee->expenses()
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->orderBy('expense_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $monthTotal      = (float) $withdrawals->sum('amount');
        $allTimeTotal    = (float) $employee->expenses()->sum('amount');
        $baseSalary      = (float) $employee->base_salary;
        $remainingSalary = $baseSalary - $monthTotal;

        // قسيمة راتب الشهر إن كانت قد أُنشئت — لعرض حالة الخصم الفعلي
        $salary = $employee->salaries()
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        return view('employees.withdrawals', compact(
            'employee', 'withdrawals', 'month', 'year',
            'monthTotal', 'allTimeTotal', 'baseSalary', 'remainingSalary', 'salary'
        ));
    }
}
