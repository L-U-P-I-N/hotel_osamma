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
        'phone',
        'hire_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'hire_date'   => 'date',
        'base_salary' => 'decimal:2',
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

    /**
     * إجمالي مسحوبات الموظف خلال شهر معيّن — يُخصم من راتب ذلك الشهر عند
     * تفعيل الخصم التلقائي. يشمل مصروفات وحدة المصروفات وسحبيات الوردية
     * المربوطة صراحةً بالموظف (فكلاهما يُنشئ سجل Expense بنفس employee_id).
     */
    public function withdrawalsTotalForMonth(int $month, int $year): float
    {
        return (float) $this->expenses()
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->sum('amount');
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
