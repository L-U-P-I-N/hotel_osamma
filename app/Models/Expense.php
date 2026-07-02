<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'amount',
        'currency',
        'category',
        'recipient_name',
        'description',
        'expense_date',
        'paid_by',
        'employee_id',
        'shift_id',
        'payment_method',
        'settled_at',
        'settled_by',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
        'settled_at'   => 'datetime',
    ];

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * الموظف الذي صُرف له المبلغ (مسحوبات موظف تُخصم من راتبه).
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function settledBy()
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    public function shift()
    {
        return $this->belongsTo(\App\Models\Shift::class);
    }

    public function cashWithdrawal()
    {
        return $this->hasOne(CashWithdrawal::class);
    }

    public function scopeByDate($query, $from, $to)
    {
        return $query->whereDate('expense_date', '>=', $from)->whereDate('expense_date', '<=', $to);
    }

    public function isPaidFromCash(): bool
    {
        return $this->payment_method === 'cash';
    }

    public static function paymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash'          => 'نقداً من الصندوق',
            'bank_transfer' => 'تحويل بنكي',
            'later'         => 'لاحقاً',
            default         => $method,
        };
    }

    public static function categoryLabel(string $category): string
    {
        return match ($category) {
            'maintenance' => 'صيانة',
            'electricity' => 'كهرباء/مياه',
            'salary'      => 'رواتب',
            'cleaning'    => 'نظافة',
            'food'        => 'طعام وشراب',
            'other'       => 'أخرى',
            default       => $category,
        };
    }

    public static function currencyLabel(string $currency): string
    {
        return match ($currency) {
            'YER' => 'ريال يمني',
            'SAR' => 'ريال سعودي',
            'USD' => 'دولار أمريكي',
            default => $currency,
        };
    }
}
