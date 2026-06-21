<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Salary;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->month);
        $year  = $request->input('year', now()->year);

        $salaries = Salary::with('employee')
            ->where('month', $month)
            ->where('year', $year)
            ->orderBy('id', 'desc')
            ->get();

        return view('salaries.index', compact('salaries', 'month', 'year'));
    }

    public function create()
    {
        $employees = Employee::where('is_active', true)->orderBy('name')->get();
        return view('salaries.create', compact('employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month'       => 'required|integer|between:1,12',
            'year'        => 'required|integer|min:2020',
            'base_salary' => 'required|numeric|min:0',
            'bonuses'     => 'nullable|numeric|min:0',
            'deductions'  => 'nullable|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        $data['bonuses']    = $data['bonuses'] ?? 0;
        $data['deductions'] = $data['deductions'] ?? 0;
        $data['net_salary'] = $data['base_salary'] + $data['bonuses'] - $data['deductions'];
        $data['created_by'] = auth()->id();

        // Check if salary already exists for this month/year/employee
        $existing = Salary::where('employee_id', $data['employee_id'])
            ->where('month', $data['month'])
            ->where('year', $data['year'])
            ->first();

        if ($existing) {
            return back()->withErrors(['error' => 'يوجد راتب مسجّل لهذا الموظف في هذا الشهر بالفعل']);
        }

        Salary::create($data);

        return redirect()->route('salaries.index', ['month' => $data['month'], 'year' => $data['year']])
            ->with('success', 'تم إنشاء قسيمة الراتب بنجاح');
    }

    public function markPaid(Request $request, Salary $salary)
    {
        $salary->update(['status' => 'paid']);
        return back()->with('success', 'تم تسجيل الراتب كمدفوع');
    }

    public function edit(Salary $salary)
    {
        if ($salary->status === 'paid') {
            return back()->withErrors(['error' => 'لا يمكن تعديل راتب مدفوع']);
        }
        $employees = Employee::where('is_active', true)->orderBy('name')->get();
        return view('salaries.edit', compact('salary', 'employees'));
    }

    public function update(Request $request, Salary $salary)
    {
        if ($salary->status === 'paid') {
            return back()->withErrors(['error' => 'لا يمكن تعديل راتب مدفوع']);
        }

        $data = $request->validate([
            'base_salary' => 'required|numeric|min:0',
            'bonuses'     => 'nullable|numeric|min:0',
            'deductions'  => 'nullable|numeric|min:0',
            'notes'       => 'nullable|string',
        ]);

        $data['bonuses']    = $data['bonuses'] ?? 0;
        $data['deductions'] = $data['deductions'] ?? 0;
        $data['net_salary'] = $data['base_salary'] + $data['bonuses'] - $data['deductions'];

        $salary->update($data);

        return redirect()->route('salaries.index', ['month' => $salary->month, 'year' => $salary->year])
            ->with('success', 'تم تحديث قسيمة الراتب بنجاح');
    }

    public function destroy(Salary $salary)
    {
        if ($salary->status === 'paid') {
            return back()->withErrors(['error' => 'لا يمكن حذف راتب مدفوع']);
        }

        $month = $salary->month;
        $year  = $salary->year;
        $salary->delete();

        return redirect()->route('salaries.index', ['month' => $month, 'year' => $year])
            ->with('success', 'تم حذف قسيمة الراتب');
    }

    public function pdf(Salary $salary)
    {
        $salary->load(['employee', 'creator']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('salaries.pdf', compact('salary'));
        $pdf->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $options = $dompdf->getOptions();
        $options->setFontDir(storage_path('fonts'));
        $options->setFontCache(storage_path('fonts'));
        $dompdf->setOptions($options);

        $filename = 'salary-' . $salary->employee->name . '-' . \App\Models\Salary::monthName($salary->month) . '-' . $salary->year . '.pdf';
        return $pdf->download($filename);
    }
}
