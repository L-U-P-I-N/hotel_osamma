<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'national_id',
        'position',
        'base_salary',
        'food_allowance',
        'phone',
        'hire_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'hire_date'   => 'date',
        'base_salary'    => 'decimal:2',
        'food_allowance' => 'decimal:2',
        'is_active'   => 'boolean',
    ];

    public function user()
    {
        return $this->hasOne(\App\Models\User::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function attendanceForDate($date)
    {
        return $this->attendances()->where('date', $date)->first();
    }

    /**
     * المصروفات المصروفة لهذا الموظف (مسحوباته من الصندوق).
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * سحبيات الوردية (الصرفيات) المربوطة صراحةً بهذا الموظف — نظير expenses()
     * لكن من شاشة الورديات (سحب نقدي فوري)، وليس وحدة المصروفات. كل سحب من نوع
     * "مصروف" يُنشئ تلقائياً سجل Expense مرتبط بنفس الموظف، فتكفي expenses()
     * وحدها لاحتساب الإجمالي دون ازدواج — هذه العلاقة للعرض/التتبع فقط.
     */
    public function withdrawals()
    {
        return $this->hasMany(CashWithdrawal::class);
    }

    /** فئة المصروفات المحتسبة على صرفية الطعام والشراب */
    public const FOOD_CATEGORY = 'food';

    /**
     * إجمالي ما صُرف للموظف خلال شهر معيّن — بكل فئاته.
     * يشمل مصروفات وحدة المصروفات وسحبيات الوردية المربوطة صراحةً بالموظف
     * (فكلاهما يُنشئ سجل Expense بنفس employee_id).
     */
    public function expensesTotalForMonth(int $month, int $year, ?string $category = null): float
    {
        return (float) $this->expenses()
            ->when($category !== null, fn($q) => $q->where('category', $category))
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->sum('amount');
    }

    /** ما صُرف من صرفية الطعام والشراب خلال الشهر */
    public function foodSpentForMonth(int $month, int $year): float
    {
        return round($this->expensesTotalForMonth($month, $year, self::FOOD_CATEGORY), 2);
    }

    /** المتبقي من صرفية الشهر — تتجدد كل شهر ولا تُرحَّل */
    public function foodRemainingForMonth(int $month, int $year): float
    {
        return round(max(0, (float) $this->food_allowance - $this->foodSpentForMonth($month, $year)), 2);
    }

    /** ما تجاوز الصرفية من مصروف طعام — هذا وحده يُخصم من الراتب */
    public function foodOverspendForMonth(int $month, int $year): float
    {
        return round(max(0, $this->foodSpentForMonth($month, $year) - (float) $this->food_allowance), 2);
    }

    /**
     * تفصيل صرفية الطعام والشراب لشهر معيّن — للعرض في كشف الموظف وقسيمة الراتب.
     */
    public function foodAllowanceSummary(int $month, int $year): array
    {
        $allowance = round((float) $this->food_allowance, 2);
        $spent     = $this->foodSpentForMonth($month, $year);

        return [
            'allowance' => $allowance,
            'spent'     => $spent,
            'remaining' => round(max(0, $allowance - $spent), 2),
            'overspend' => round(max(0, $spent - $allowance), 2),
            'percent'   => $allowance > 0 ? min(100, round($spent / $allowance * 100)) : 0,
        ];
    }

    /**
     * ما يُخصم من الراتب الأساسي: كل المصروفات غير الطعام، زائد ما تجاوز
     * صرفية الطعام فقط. الصرفية بند مستقل يتجدد شهرياً فلا تُخصم من الراتب.
     */
    public function withdrawalsTotalForMonth(int $month, int $year): float
    {
        $all  = $this->expensesTotalForMonth($month, $year);
        $food = $this->foodSpentForMonth($month, $year);

        return round(($all - $food) + $this->foodOverspendForMonth($month, $year), 2);
    }

    /**
     * عدد أيام الغياب (سجل حضور بحالة "غائب") لهذا الموظف خلال شهر معيّن.
     */
    public function absentDaysForMonth(int $month, int $year): int
    {
        return $this->attendances()
            ->where('status', 'absent')
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->count();
    }

    /**
     * عدد أيام الإجازة بدون راتب (من سجلات الإجازات الرسمية) الواقعة ضمن شهر
     * معيّن — يُحتسب تقاطع فترة كل إجازة مع الشهر (فقد تمتد الإجازة عبر شهرين).
     */
    public function unpaidLeaveDaysForMonth(int $month, int $year): int
    {
        $start = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth();

        $leaves = $this->leaves()
            ->where('type', 'unpaid')
            ->where('from_date', '<=', $end)
            ->where('to_date', '>=', $start)
            ->get();

        $days = 0;
        foreach ($leaves as $leave) {
            $overlapStart = $leave->from_date->greaterThan($start) ? $leave->from_date : $start;
            $overlapEnd   = $leave->to_date->lessThan($end) ? $leave->to_date : $end;
            $days += $overlapStart->diffInDays($overlapEnd) + 1;
        }

        return $days;
    }

    /** قيمة اليوم الواحد من الراتب الأساسي، وفق عدد أيام الشهر الفعلي. */
    public function dailyRateFor(int $month, int $year): float
    {
        $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
        return $daysInMonth > 0 ? round((float) $this->base_salary / $daysInMonth, 2) : 0.0;
    }

    /**
     * خصم الغياب/الإجازة بدون راتب لشهر معيّن — تفصيل كامل (أيام + قيمة اليوم +
     * الإجمالي) ليُعرض للموظف المسؤول قبل تطبيقه على قسيمة الراتب.
     */
    public function attendanceDeductionForMonth(int $month, int $year): array
    {
        $absentDays      = $this->absentDaysForMonth($month, $year);
        $unpaidLeaveDays = $this->unpaidLeaveDaysForMonth($month, $year);
        $dailyRate       = $this->dailyRateFor($month, $year);
        $totalDays       = $absentDays + $unpaidLeaveDays;

        return [
            'absent_days'       => $absentDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'total_days'        => $totalDays,
            'daily_rate'        => $dailyRate,
            'amount'            => round($totalDays * $dailyRate, 2),
        ];
    }
}
