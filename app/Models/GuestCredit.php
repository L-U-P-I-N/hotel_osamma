<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * مبلغ متبقٍّ لنزيل (أمانة دائنة عليه الفندق): فرق ليالٍ دُفعت ولم يُقِمها،
 * أو أي مبلغ زائد لم يُصرف له وقت الخروج. يبقى قائماً حتى يُصرف له أو يتنازل.
 */
class GuestCredit extends Model
{
    use HasFactory;

    public const STATUS_OPEN      = 'open';
    public const STATUS_SETTLED   = 'settled';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_LABELS = [
        self::STATUS_OPEN      => 'مستحق للنزيل',
        self::STATUS_SETTLED   => 'صُرف للنزيل',
        self::STATUS_CANCELLED => 'تنازل عنه النزيل',
    ];

    protected $fillable = [
        'reservation_id', 'guest_id', 'amount', 'currency', 'status', 'reason', 'created_by',
        'settled_at', 'settled_by', 'settlement_method', 'settlement_notes', 'refund_id',
    ];

    protected $casts = [
        'amount'     => 'decimal:2',
        'settled_at' => 'datetime',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function reservation() { return $this->belongsTo(Reservation::class); }
    public function guest()       { return $this->belongsTo(Guest::class); }
    public function refund()      { return $this->belongsTo(Refund::class); }
    public function createdBy()   { return $this->belongsTo(User::class, 'created_by'); }
    public function settledBy()   { return $this->belongsTo(User::class, 'settled_by'); }
}
