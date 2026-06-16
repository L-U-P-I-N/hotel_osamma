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
        'withdrawal_type','exchange_to_currency','exchange_to_amount',
    ];

    protected $casts = [
        'amount'             => 'decimal:2',
        'exchange_to_amount' => 'decimal:2',
        'withdrawal_date'    => 'datetime',
    ];

    public function cashSettlement() { return $this->belongsTo(CashSettlement::class); }
    public function shift()          { return $this->belongsTo(Shift::class); }

    public function getTypeLabelAttribute(): string
    {
        return $this->withdrawal_type === 'currency_exchange' ? 'صرف عملة' : 'مصروف';
    }

    public function isExchange(): bool
    {
        return $this->withdrawal_type === 'currency_exchange';
    }
}
