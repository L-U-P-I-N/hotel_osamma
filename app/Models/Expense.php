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
        'supplier_id',
        'description',
        'expense_date',
        'paid_by',
        'shift_id',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function shift()
    {
        return $this->belongsTo(\App\Models\Shift::class);
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
