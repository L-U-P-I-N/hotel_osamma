<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CashWithdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'cash_settlement_id','shift_id','amount','currency',
        'withdrawal_date','withdrawn_by_name','handed_by_name','notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'withdrawal_date' => 'datetime',
    ];

    public function cashSettlement() { return $this->belongsTo(CashSettlement::class); }
    public function shift()          { return $this->belongsTo(Shift::class); }
}
