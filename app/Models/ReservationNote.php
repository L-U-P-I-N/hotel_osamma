<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ملاحظة فورية على حجز — تظهر كأيقونة في جدول الحجوزات ليراها أي موظف دون
 * فتح صفحة التفاصيل.
 */
class ReservationNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['reservation_id', 'type', 'body', 'resolved_at', 'resolved_by', 'created_by'];

    protected $casts = ['resolved_at' => 'datetime'];

    public const TYPES = [
        'obligation'  => 'التزام قبل المغادرة',
        'maintenance' => 'صيانة مطلوبة',
        'payment'     => 'رسوم معلّقة',
        'general'     => 'تنبيه عام',
    ];

    /** لون الأيقونة يتبع أشدّ ملاحظة قائمة، فيُقرأ الحال من نظرة واحدة. */
    public const TYPE_COLORS = [
        'obligation'  => 'red',
        'payment'     => 'red',
        'maintenance' => 'amber',
        'general'     => 'blue',
    ];

    public static function typeLabel(?string $type): string
    {
        return self::TYPES[$type] ?? ($type ?: 'تنبيه عام');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabel($this->type);
    }

    public function getColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'blue';
    }

    public function getIsResolvedAttribute(): bool
    {
        return $this->resolved_at !== null;
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
