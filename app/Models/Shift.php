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
        'total_refunds_yer','total_refunds_sar','total_refunds_usd',
        'actual_amount','shortfall',
        'salary_deducted_at','salary_deducted_by',
        'employee_signature','admin_signature','notes','locked_by',
        'close_events',
    ];

    protected $casts = [
        'shift_date'          => 'date',
        'started_at'          => 'datetime',
        'ended_at'            => 'datetime',
        'closed_at'           => 'datetime',
        'salary_deducted_at'  => 'datetime',
        'is_closed'           => 'boolean',
        'close_events'        => 'array',
    ];

    public function user()        { return $this->belongsTo(User::class); }
    public function lockedBy()    { return $this->belongsTo(User::class, 'locked_by'); }
    public function payments()    { return $this->hasMany(Payment::class); }
    public function withdrawals() { return $this->hasMany(CashWithdrawal::class); }
    public function refunds()     { return $this->hasMany(Refund::class); }

    public function getNetBalanceYerAttribute(): float
    {
        return $this->total_received_yer - $this->total_withdrawals_yer - $this->total_refunds_yer;
    }

    public function getNetBalanceSarAttribute(): float
    {
        return $this->total_received_sar - $this->total_withdrawals_sar - $this->total_refunds_sar;
    }

    public function getNetBalanceUsdAttribute(): float
    {
        return $this->total_received_usd - $this->total_withdrawals_usd - $this->total_refunds_usd;
    }

    /**
     * دفعات الوردية مجمّعة: عدة دفعات لنفس الحجز، نفس المستلم، نفس العملة
     * وطريقة الدفع (مثلاً دفعة جزئية ثم باقي المبلغ لاحقاً في نفس الوردية)
     * تُعرض كصف واحد بمجموع المبلغ بدل صف منفصل لكل دفعة.
     */
    public function groupedPayments()
    {
        return $this->payments
            ->groupBy(fn ($p) => implode('|', [$p->reservation_id, $p->received_by, $p->currency, $p->method, $p->type]))
            ->map(function ($group) {
                $first = $group->first();
                return (object) [
                    'reservation'       => $first->reservation,
                    'received_by'       => $first->received_by,
                    'currency'          => $first->currency,
                    'method'            => $first->method,
                    'type'              => $first->type,
                    'amount'            => $group->sum(fn ($p) => (float) $p->amount),
                    'count'             => $group->count(),
                    'created_at'        => $group->max('created_at'),
                    'bank_transfer_ref' => $group->pluck('bank_transfer_ref')->filter()->unique()->implode('، '),
                ];
            })
            ->values();
    }
}
