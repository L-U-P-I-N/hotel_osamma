<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = [
        'user_id','shift_date','started_at','ended_at',
        'is_closed','closed_at',
        'total_received_yer','total_received_sar','total_received_usd',
        'total_withdrawals_yer','total_withdrawals_sar','total_withdrawals_usd',
        'actual_amount','shortfall',
        'salary_deducted_at','salary_deducted_by',
        'employee_signature','admin_signature','notes','locked_by',
    ];

    protected $casts = [
        'shift_date'          => 'date',
        'started_at'          => 'datetime',
        'ended_at'            => 'datetime',
        'closed_at'           => 'datetime',
        'salary_deducted_at'  => 'datetime',
        'is_closed'           => 'boolean',
    ];

    public function user()        { return $this->belongsTo(User::class); }
    public function lockedBy()    { return $this->belongsTo(User::class, 'locked_by'); }
    public function payments()    { return $this->hasMany(Payment::class); }
    public function withdrawals() { return $this->hasMany(CashWithdrawal::class); }

    public function getNetBalanceYerAttribute(): float
    {
        return $this->total_received_yer - $this->total_withdrawals_yer;
    }

    public function getNetBalanceSarAttribute(): float
    {
        return $this->total_received_sar - $this->total_withdrawals_sar;
    }

    public function getNetBalanceUsdAttribute(): float
    {
        return $this->total_received_usd - $this->total_withdrawals_usd;
    }
}
