<?php
namespace App\Models;

use App\Models\Salary;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'year','month','revenue_target','expense_limit','notes','created_by',
    ];

    protected $casts = [
        'revenue_target' => 'decimal:2',
        'expense_limit'  => 'decimal:2',
    ];

    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public function getMonthNameAttribute(): string
    {
        return Salary::monthName($this->month);
    }
}
