<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Account extends Model
{
    protected $fillable = ['code', 'name', 'type', 'parent_id', 'is_active', 'normal_balance'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalLines()
    {
        return $this->hasMany(JournalLine::class);
    }

    public function scopeLeaf(Builder $query): Builder
    {
        return $query->whereDoesntHave('children');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * رصيد الحساب = مجموع المدين - مجموع الدائن، أو العكس حسب طبيعة الحساب
     * (normal_balance) بحيث يكون الرصيد الطبيعي دائماً موجباً.
     */
    public function getBalanceAttribute(): float
    {
        $sums = $this->journalLines()
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        $debit = (float) ($sums->total_debit ?? 0);
        $credit = (float) ($sums->total_credit ?? 0);

        return $this->normal_balance === 'debit' ? $debit - $credit : $credit - $debit;
    }
}
