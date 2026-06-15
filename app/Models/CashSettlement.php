<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashSettlement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'shift_date', 'total_received', 'total_withdrawals', 'net_balance',
        'employee_signature', 'admin_signature', 'status', 'locked_by', 'locked_at',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'total_received' => 'decimal:2',
        'total_withdrawals' => 'decimal:2',
        'net_balance' => 'decimal:2',
        'locked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function withdrawals()
    {
        return $this->hasMany(CashWithdrawal::class);
    }

    public function getNetBalanceAttribute(): float
    {
        return (float)$this->total_received - (float)$this->total_withdrawals;
    }
}
