<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'base_salary',
        'bonuses',
        'deductions',
        'withdrawals_deduction',
        'attendance_deduction',
        'net_salary',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'base_salary'            => 'decimal:2',
        'bonuses'                => 'decimal:2',
        'deductions'             => 'decimal:2',
        'withdrawals_deduction'  => 'decimal:2',
        'attendance_deduction'   => 'decimal:2',
        'net_salary'             => 'decimal:2',
    ];

    /** إجمالي كل الخصومات (اليدوية + المسحوبات التلقائية + الغياب التلقائي). */
    public function getTotalDeductionsAttribute(): float
    {
        return round((float) $this->deductions + (float) $this->withdrawals_deduction + (float) $this->attendance_deduction, 2);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function monthName(int $month): string
    {
        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];
        return $months[$month] ?? $month;
    }
}
