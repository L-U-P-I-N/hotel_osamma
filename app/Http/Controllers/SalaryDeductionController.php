<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Salary;
use App\Models\SalaryDeduction;
use Illuminate\Http\Request;

/**
 * خصومات رواتب الموظفين: تُسجَّل وقت حدوثها بسببها وتاريخها ومَن سجّلها، ثم
 * تُجمع خصومات الشهر تلقائياً في قسيمة راتب ذلك الشهر — بدل رقمٍ مجرَّد يُكتب
 * في القسيمة آخر الشهر بلا سبب ولا مسؤول ولا سجلّ يُراجَع.
 */
class SalaryDeductionController extends Controller
{
    /** سجل خصومات موظف واحد لشهر محدَّد، مع نموذج تسجيل خصم جديد. */
    public function index(Request $request, Employee $employee)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        return view('employees.deductions', $this->deductionsData($employee, $month, $year));
    }

    public function store(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'amount'         => 'required|numeric|min:0.01',
            'reason'         => 'required|in:' . implode(',', array_keys(SalaryDeduction::REASONS)),
            'description'    => 'nullable|string|max:1000',
            'deduction_date' => 'required|date|before_or_equal:today',
        ], [
            'amount.min'                    => 'يجب أن يكون مبلغ الخصم أكبر من صفر',
            'amount.required'               => 'مبلغ الخصم مطلوب',
            'reason.required'               => 'سبب الخصم مطلوب',
            'deduction_date.before_or_equal' => 'لا يمكن تسجيل خصم بتاريخ مستقبلي',
        ]);

        $data['employee_id'] = $employee->id;
        $data['created_by']  = auth()->id();

        $deduction = SalaryDeduction::create($data);

        // قسيمة راتب ذلك الشهر — إن كانت أُنشئت ولم تُدفع بعد — تُحدَّث فوراً
        // كي لا يبقى الخصم معلّقاً خارجها حتى ينتبه أحد لإعادة الاحتساب.
        $this->syncSalarySlip($employee, $deduction->deduction_date->month, $deduction->deduction_date->year);

        return back()->with('success', 'تم تسجيل الخصم على راتب الموظف');
    }

    public function destroy(Employee $employee, SalaryDeduction $deduction)
    {
        abort_unless($deduction->employee_id === $employee->id, 404);

        $month = $deduction->deduction_date->month;
        $year  = $deduction->deduction_date->year;

        $deduction->delete();
        $this->syncSalarySlip($employee, $month, $year);

        return back()->with('success', 'تم حذف الخصم');
    }

    /**
     * إعادة مزامنة قسيمة راتب الشهر مع مجموع خصوماته المسجَّلة. القسيمة
     * المدفوعة لا تُمسّ: أرقامها صُرفت فعلاً، وتعديلها يُخلّ بما استلمه الموظف.
     */
    private function syncSalarySlip(Employee $employee, int $month, int $year): void
    {
        $salary = Salary::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', $year)
            ->first();

        if (!$salary || $salary->status === 'paid') {
            return;
        }

        $salary->recorded_deductions = $employee->recordedDeductionsForMonth($month, $year);
        // الصافي قد يخرج سالباً حين تتجاوز الخصومات الراتب — يُعرض بالأحمر
        // ويبقى ديناً على الموظف، ولا يُصفَّر كي لا يضيع الفارق بلا أثر.
        $salary->net_salary = round(
            (float) $salary->base_salary + (float) $salary->bonuses - $salary->total_deductions, 2
        );
        $salary->save();
    }

    /** بيانات صفحة الخصومات (مشتركة بين الشاشة والتصدير مستقبلاً). */
    private function deductionsData(Employee $employee, int $month, int $year): array
    {
        $deductions = $employee->salaryDeductions()
            ->with('createdBy')
            ->whereMonth('deduction_date', $month)
            ->whereYear('deduction_date', $year)
            ->orderByDesc('deduction_date')
            ->orderByDesc('id')
            ->get();

        $monthTotal   = round((float) $deductions->sum('amount'), 2);
        $allTimeTotal = round((float) $employee->salaryDeductions()->sum('amount'), 2);
        $baseSalary   = (float) $employee->base_salary;

        $salary = Salary::where('employee_id', $employee->id)
            ->where('month', $month)->where('year', $year)->first();

        return compact(
            'employee', 'deductions', 'month', 'year',
            'monthTotal', 'allTimeTotal', 'baseSalary', 'salary'
        );
    }
}
