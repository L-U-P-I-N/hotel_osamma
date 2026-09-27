<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * ملاحظة عامة على الفندق — ليست على حجز ولا غرفة. تظهر في أعلى صفحة الحجوزات
 * ليقرأها كل موظف يفتحها (تنبيه للوردية التالية، أمر إداري، عطل عام…).
 */
class HotelNote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['type', 'body', 'is_pinned', 'resolved_at', 'resolved_by', 'created_by'];

    protected $casts = [
        'is_pinned'   => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public const TYPES = [
        'urgent'      => 'عاجل — انتباه',
        'handover'    => 'تسليم وردية',
        'maintenance' => 'صيانة عامة',
        'general'     => 'ملاحظة عامة',
    ];

    /** لون البطاقة يتبع النوع، فيُقرأ مستوى الأهمية من نظرة واحدة. */
    public const TYPE_COLORS = [
        'urgent'      => 'red',
        'handover'    => 'amber',
        'maintenance' => 'blue',
        'general'     => 'slate',
    ];

    public static function typeLabel(?string $type): string
    {
        return self::TYPES[$type] ?? ($type ?: 'ملاحظة عامة');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeLabel($this->type);
    }

    public function getColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'slate';
    }

    public function getIsResolvedAttribute(): bool
    {
        return $this->resolved_at !== null;
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }

    /** المثبّتة أولاً ثم الأحدث — ترتيب القراءة في أعلى الصفحة. */
    public function scopeBoardOrder($query)
    {
        return $query->orderByDesc('is_pinned')->orderByDesc('id');
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
