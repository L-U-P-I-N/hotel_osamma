<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * خصم على راتب موظف: عقوبة أو تعويض تلف أو ما شابه، يُسجَّل وقت حدوثه بسببه
 * وتاريخه ومَن سجّله. تُجمع خصومات الشهر تلقائياً في قسيمة الراتب.
 */
class SalaryDeduction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id', 'amount', 'reason', 'description', 'deduction_date', 'created_by',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'deduction_date' => 'date',
    ];

    public const REASONS = [
        'lateness'   => 'تأخير عن الدوام',
        'absence'    => 'غياب',
        'damage'     => 'إتلاف أو فقد عهدة',
        'shortage'   => 'عجز في التسليم النقدي',
        'violation'  => 'مخالفة لائحة العمل',
        'advance'    => 'استرداد سلفة',
        'other'      => 'أخرى',
    ];

    public static function reasonLabel(?string $reason): string
    {
        return self::REASONS[$reason] ?? ($reason ?: '—');
    }

    public function getReasonLabelAttribute(): string
    {
        return self::reasonLabel($this->reason);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
