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

        $employee = Employee::findOrFail($data['employee_id']);
        [$data['withdrawals_deduction'], $data['attendance_deduction'], $data['notes']] =
            $this->computeAutoDeductions($request, $employee, (int) $data['month'], (int) $data['year'], $data['notes'] ?? null);

        $data['net_salary'] = $data['base_salary'] + $data['bonuses']
            - $data['deductions'] - $data['withdrawals_deduction'] - $data['attendance_deduction'];
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

    /**
     * يحتسب خصمَي المسحوبات والغياب/الإجازة بدون راتب (كلٌّ اختياري عبر
     * checkbox منفصلة). عند تفعيل خانة ما، تُعاد احتساب قيمتها بالكامل من
     * الصفر بناءً على بيانات الشهر الحالية (استبدال لا إضافة) — فتبقى آمنة
     * حتى لو فُعِّلت أكثر من مرة (عند تعديل القسيمة) دون ازدواج. عند عدم
     * تفعيلها، تبقى القيمة كما هي في $keepWithdrawals/$keepAttendance (القيمة
     * المحفوظة سابقاً عند التعديل، أو 0 عند الإنشاء الأول).
     */
    private function computeAutoDeductions(
        Request $request,
        Employee $employee,
        int $month,
        int $year,
        ?string $notes,
        float $keepWithdrawals = 0.0,
        float $keepAttendance = 0.0
    ): array {
        $withdrawalsDeduction = $keepWithdrawals;
        $attendanceDeduction  = $keepAttendance;
        $notes = $notes ?? '';

        if ($request->boolean('include_withdrawals')) {
            // صرفية الطعام والشراب بند مستقل يتجدد شهرياً، فلا تدخل الخصم إلا
            // بما تجاوزها. نوضّح ذلك في الملاحظات حتى لا يبدو الخصم ناقصاً.
            $withdrawalsDeduction = $employee->withdrawalsTotalForMonth($month, $year);
            $food = $employee->foodAllowanceSummary($month, $year);

            if ($withdrawalsDeduction > 0) {
                $notes = trim($notes . "\n" . 'خصم مسحوبات ' . Salary::monthName($month) . ' ' . $year
                    . ': ' . number_format($withdrawalsDeduction, 0) . ' ر.ي'
                    . ($food['overspend'] > 0
                        ? ' (منها ' . number_format($food['overspend'], 0) . ' ر.ي تجاوزاً لصرفية الطعام والشراب)'
                        : ''));
            }

            if ($food['allowance'] > 0) {
                $notes = trim($notes . "\n" . 'صرفية طعام وشراب ' . Salary::monthName($month) . ' ' . $year
                    . ': صُرف ' . number_format($food['spent'], 0) . ' من ' . number_format($food['allowance'], 0)
                    . ' ر.ي — لا تُخصم من الراتب');
            }
        }

        if ($request->boolean('include_attendance_deduction')) {
            $att = $employee->attendanceDeductionForMonth($month, $year);
            $attendanceDeduction = $att['amount'];
            if ($attendanceDeduction > 0) {
                $notes = trim($notes . "\n" . 'خصم غياب/إجازة بدون راتب ' . Salary::monthName($month) . ' ' . $year
                    . ': ' . $att['total_days'] . ' يوم (غياب ' . $att['absent_days'] . ' + إجازة بدون راتب ' . $att['unpaid_leave_days'] . ') × '
                    . number_format($att['daily_rate'], 0) . ' ر.ي = ' . number_format($attendanceDeduction, 0) . ' ر.ي');
            }
        }

        return [$withdrawalsDeduction, $attendanceDeduction, $notes ?: null];
    }

    public function markPaid(Request $request, Salary $salary)
    {
        $salary->update(['status' => 'paid']);

        // يسدّ فجوة كانت موجودة: "مدفوع" لا ينشئ أي سجل مالي. راتب مستحق
        // يُسدَّد الآن من الصندوق العام.
        app(\App\Services\JournalService::class)->post(
            now()->toDateString(),
            'راتب مدفوع: ' . ($salary->employee?->name ?? '—') . ' — ' . $salary->month . '/' . $salary->year,
            Salary::class,
            $salary->id,
            [
                ['account_code' => '2100', 'debit' => $salary->net_salary],
                ['account_code' => '1120', 'credit' => $salary->net_salary],
            ],
            auth()->id()
        );

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

        [$data['withdrawals_deduction'], $data['attendance_deduction'], $data['notes']] =
            $this->computeAutoDeductions(
                $request, $salary->employee, $salary->month, $salary->year, $data['notes'] ?? null,
                (float) $salary->withdrawals_deduction, (float) $salary->attendance_deduction
            );

        $data['net_salary'] = $data['base_salary'] + $data['bonuses']
            - $data['deductions'] - $data['withdrawals_deduction'] - $data['attendance_deduction'];

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
        $pdf = pdf_load_view('salaries.pdf', compact('salary'));
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
