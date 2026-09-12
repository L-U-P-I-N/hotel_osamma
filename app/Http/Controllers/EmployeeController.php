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
            'base_salary'    => 'required|numeric|min:0',
            'food_allowance' => 'nullable|numeric|min:0',
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
            'base_salary'    => 'required|numeric|min:0',
            'food_allowance' => 'nullable|numeric|min:0',
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

        return view('employees.withdrawals', $this->withdrawalsData($employee, $month, $year));
    }

    /** تصدير كشف مسحوبات موظف واحد لشهر محدَّد إلى PDF. */
    public function withdrawalsPdf(Request $request, Employee $employee)
    {
        $month = (int) $request->input('month', now()->month);
        $year  = (int) $request->input('year', now()->year);

        $pdf = pdf_load_view('employees.withdrawals_pdf', $this->withdrawalsData($employee, $month, $year));
        $pdf->setPaper('a4', 'portrait');

        return $this->hrPdf($pdf)->download('مسحوبات-' . $employee->name . '-' . $month . '-' . $year . '.pdf');
    }

    /** بيانات كشف المسحوبات (مشتركة بين الشاشة والتصدير). */
    private function withdrawalsData(Employee $employee, int $month, int $year): array
    {
        $withdrawals = $employee->expenses()
            ->with(['paidBy', 'shift.user'])
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->orderBy('expense_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $monthTotal   = (float) $withdrawals->sum('amount');
        $allTimeTotal = (float) $employee->expenses()->sum('amount');
        $baseSalary   = (float) $employee->base_salary;

        $foodSummary      = $employee->foodAllowanceSummary($month, $year);
        $salaryChargeable = $employee->withdrawalsTotalForMonth($month, $year);
        $remainingSalary  = $baseSalary - $salaryChargeable;

        $salary = $employee->salaries()->where('month', $month)->where('year', $year)->first();

        return compact(
            'employee', 'withdrawals', 'month', 'year',
            'monthTotal', 'allTimeTotal', 'baseSalary', 'remainingSalary', 'salary',
            'foodSummary', 'salaryChargeable'
        );
    }

    /**
     * كشف حساب موظف واحد: الرواتب المستحقة والمدفوعة، السلف/المسحوبات،
     * الحضور والغياب، والإجازات خلال فترة، مع صافي المستحق له.
     */
    public function statement(Request $request, Employee $employee)
    {
        $data = $this->buildStatementData($employee, ...$this->statementRange($request));

        return view('employees.statement', $data);
    }

    public function statementPdf(Request $request, Employee $employee)
    {
        $data = $this->buildStatementData($employee, ...$this->statementRange($request));

        $pdf = pdf_load_view('employees.statement_pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        return $this->hrPdf($pdf)->download('كشف-حساب-' . $employee->name . '.pdf');
    }

    /** كشف مجمَّع لكل الموظفين — صف لكل موظف بإجمالياته خلال الفترة. */
    public function statements(Request $request)
    {
        $data = $this->buildAllStatementsData(...$this->statementRange($request));

        return view('employees.statements', $data);
    }

    public function statementsPdf(Request $request)
    {
        $data = $this->buildAllStatementsData(...$this->statementRange($request));

        $pdf = pdf_load_view('employees.statements_pdf', $data);
        $pdf->setPaper('a4', 'landscape');

        return $this->hrPdf($pdf)->download('كشف-حساب-الموظفين.pdf');
    }

    /** الفترة الافتراضية: من بداية السنة الحالية إلى اليوم. */
    private function statementRange(Request $request): array
    {
        return [
            $request->input('from', now()->startOfYear()->toDateString()),
            $request->input('to', now()->toDateString()),
        ];
    }

    private function hrPdf(\Barryvdh\DomPDF\PDF $pdf): \Barryvdh\DomPDF\PDF
    {
        $dompdf = $pdf->getDomPDF();
        $opts   = $dompdf->getOptions();
        $opts->setFontDir(storage_path('fonts'));
        $opts->setFontCache(storage_path('fonts'));
        $dompdf->setOptions($opts);

        return $pdf;
    }

    /**
     * الراتب شهري لا يحمل تاريخاً مفرداً، فنقارن بترتيب الشهر (سنة×12+شهر)
     * بدل مقارنة تواريخ — أبسط وأدقّ عند تداخل الفترة مع منتصف شهر.
     */
    private function monthsInRange(string $from, string $to): array
    {
        $f = \Carbon\Carbon::parse($from);
        $t = \Carbon\Carbon::parse($to);

        return [$f->year * 12 + $f->month, $t->year * 12 + $t->month];
    }

    private function buildStatementData(Employee $employee, string $from, string $to): array
    {
        [$minMonth, $maxMonth] = $this->monthsInRange($from, $to);

        $salaries = $employee->salaries()
            ->orderBy('year')->orderBy('month')
            ->get()
            ->filter(fn($s) => ($s->year * 12 + $s->month) >= $minMonth && ($s->year * 12 + $s->month) <= $maxMonth)
            ->values();

        // السلف/المسحوبات: expenses كافية وحدها — كل سحب وردية مربوط بموظف
        // يُنشئ سجل Expense بنفس employee_id، فاستخدام الاثنين يُضاعف الاحتساب.
        // نُحمِّل من صرفها ووردِيّتها معها: كشف الحساب يجب أن يُجيب "من صرف هذا
        // المبلغ ومتى وفي وردية من" لا أن يعرض رقماً مجرَّداً.
        $advances = $employee->expenses()
            ->with(['paidBy', 'shift.user'])
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->orderBy('expense_date')
            ->get();

        $attendance = $employee->attendances()
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->get();

        $leaves = $employee->leaves()
            ->whereDate('from_date', '<=', $to)
            ->whereDate('to_date', '>=', $from)
            ->orderBy('from_date')
            ->get();

        // تفصيل شهري: راتب الشهر، ما صُرف منه فعلاً، والمتبقي له — هذا جوهر
        // سؤال "كم راتبه وكم المتبقي منه". صرفية الطعام بند مستقل لا يُخصم من
        // الراتب إلا ما تجاوزها، فنعرض العمودين منفصلين حتى يتضح سبب كل خصم.
        $monthly = collect($this->monthsBetween($from, $to))->map(function (array $m) use ($employee, $salaries) {
            $food       = $employee->foodAllowanceSummary($m['month'], $m['year']);
            $chargeable = $employee->withdrawalsTotalForMonth($m['month'], $m['year']);
            $base       = (float) $employee->base_salary;
            $slip       = $salaries->first(fn($s) => (int) $s->month === $m['month'] && (int) $s->year === $m['year']);

            return [
                'month'       => $m['month'],
                'year'        => $m['year'],
                'base'        => $base,
                'food_spent'  => $food['spent'],
                'food_allow'  => $food['allowance'],
                'chargeable'  => $chargeable,
                'remaining'   => round($base - $chargeable, 2),
                'slip'        => $slip,
            ];
        });

        $totals = [
            'salaries_net'   => (float) $salaries->sum('net_salary'),
            'salaries_paid'  => (float) $salaries->where('status', 'paid')->sum('net_salary'),
            'salaries_due'   => (float) $salaries->where('status', '!=', 'paid')->sum('net_salary'),
            'advances'       => (float) $advances->sum('amount'),
            'deductions'     => (float) $salaries->sum(fn($s) => (float) $s->deductions + (float) $s->withdrawals_deduction + (float) $s->attendance_deduction),
            'bonuses'        => (float) $salaries->sum('bonuses'),
            'present_days'   => $attendance->where('status', 'present')->count(),
            'absent_days'    => $attendance->where('status', 'absent')->count(),
            'late_days'      => $attendance->where('status', 'late')->count(),
            'leave_days'     => (int) $leaves->sum('days'),
            // مجاميع التفصيل الشهري
            'food_spent'     => round((float) $monthly->sum('food_spent'), 2),
            'chargeable'     => round((float) $monthly->sum('chargeable'), 2),
            'remaining'      => round((float) $monthly->sum('remaining'), 2),
        ];

        return compact('employee', 'salaries', 'advances', 'attendance', 'leaves', 'monthly', 'totals', 'from', 'to');
    }

    /** كل شهر يتقاطع مع الفترة [from, to] بالترتيب — لبناء التفصيل الشهري. */
    private function monthsBetween(string $from, string $to): array
    {
        $cursor = \Carbon\Carbon::parse($from)->startOfMonth();
        $end    = \Carbon\Carbon::parse($to)->startOfMonth();
        $months = [];

        while ($cursor->lte($end)) {
            $months[] = ['month' => (int) $cursor->month, 'year' => (int) $cursor->year];
            $cursor->addMonth();
        }

        return $months;
    }

    private function buildAllStatementsData(string $from, string $to): array
    {
        [$minMonth, $maxMonth] = $this->monthsInRange($from, $to);

        $months = $this->monthsBetween($from, $to);

        // ترتيب أبجدي عربي صحيح: ترتيب MySQL/sqlite الثنائي يضع الهمزات
        // والألف المقصورة في غير موضعها، فنرتّب بمقارنة نصية محلية بعد الجلب.
        $employees = Employee::get()->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values();

        $rows = $employees->map(function (Employee $employee, int $i) use ($from, $to, $minMonth, $maxMonth, $months) {
            $salaries = $employee->salaries()->get()->filter(
                fn($s) => ($s->year * 12 + $s->month) >= $minMonth && ($s->year * 12 + $s->month) <= $maxMonth
            );

            // تفاصيل المسحوبات تُحمَّل مع الصف كي يحوي التصدير الجماعي كشف كل
            // موظف مفصَّلاً، لا مجرد إجماليات.
            $advanceRows = $employee->expenses()
                ->with(['paidBy', 'shift.user'])
                ->whereDate('expense_date', '>=', $from)
                ->whereDate('expense_date', '<=', $to)
                ->orderBy('expense_date')
                ->orderBy('id')
                ->get();

            $advances = (float) $advanceRows->sum('amount');

            // ما يُخصم فعلاً من الراتب (بلا صرفية الطعام ضمن حدّها) والمتبقي له
            $chargeable = 0.0;
            $foodSpent  = 0.0;
            foreach ($months as $m) {
                $chargeable += $employee->withdrawalsTotalForMonth($m['month'], $m['year']);
                $foodSpent  += $employee->foodAllowanceSummary($m['month'], $m['year'])['spent'];
            }

            $base = (float) $employee->base_salary * max(1, count($months));

            return [
                'seq'            => $i + 1,
                'employee'       => $employee,
                'base_total'     => $base,
                'salaries_net'   => (float) $salaries->sum('net_salary'),
                'salaries_paid'  => (float) $salaries->where('status', 'paid')->sum('net_salary'),
                'salaries_due'   => (float) $salaries->where('status', '!=', 'paid')->sum('net_salary'),
                'advances'       => $advances,
                'advance_rows'   => $advanceRows,
                'food_spent'     => round($foodSpent, 2),
                'chargeable'     => round($chargeable, 2),
                'remaining'      => round($base - $chargeable, 2),
                'months_count'   => $salaries->count(),
            ];
        });

        $totals = [
            'base_total'    => $rows->sum('base_total'),
            'salaries_net'  => $rows->sum('salaries_net'),
            'salaries_paid' => $rows->sum('salaries_paid'),
            'salaries_due'  => $rows->sum('salaries_due'),
            'advances'      => $rows->sum('advances'),
            'food_spent'    => $rows->sum('food_spent'),
            'chargeable'    => $rows->sum('chargeable'),
            'remaining'     => $rows->sum('remaining'),
        ];

        return compact('rows', 'totals', 'from', 'to');
    }
}
