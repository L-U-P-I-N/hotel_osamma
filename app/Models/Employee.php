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
     * إجمالي مسحوبات الموظف خلال شهر معيّن — يُخصم من راتب ذلك الشهر.
     */
    public function withdrawalsTotalForMonth(int $month, int $year): float
    {
        return (float) $this->expenses()
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->sum('amount');
    }
}
