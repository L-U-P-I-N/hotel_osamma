<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'guest_id','room_id','linked_room_id','suite_booking_type','created_by',
        'check_in_date','check_in_time','check_out_date','check_out_time','actual_check_out','origin','purpose','notes',
        'status','payment_status','total_amount','renewal_price_per_night','paid_amount','currency',
        'admin_approval_id','government_exported','government_exported_at',
        'discount_type','discount_value','discount_amount','discount_reason',
        'cancellation_reason','cancelled_by','cancelled_at',
    ];

    public function getCurrencySymbolAttribute(): string
    {
        return match (strtoupper($this->currency ?? 'YER')) {
            'SAR'   => 'ر.س',
            'USD'   => '$',
            default => 'ر.ي',
        };
    }

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'actual_check_out' => 'datetime',
        'government_exported' => 'boolean',
        'government_exported_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'renewal_price_per_night' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class)->withTrashed();
    }

    public function room()       { return $this->belongsTo(Room::class); }
    public function linkedRoom() { return $this->belongsTo(Room::class, 'linked_room_id'); }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adminApproval()
    {
        return $this->belongsTo(User::class, 'admin_approval_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function companions()
    {
        return $this->hasMany(Companion::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function roomInspections()
    {
        return $this->hasMany(RoomInspection::class);
    }

    public function extraCharges()
    {
        return $this->hasMany(ExtraCharge::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'checked_in');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('check_in_date', today());
    }

    public function scopeCheckedIn(Builder $query): Builder
    {
        return $query->where('status', 'checked_in');
    }

    /**
     * الحجوزات الملغاة فقط — محذوفة حذفاً ناعماً وتحمل سبب إلغاء، لعرضها في
     * تقرير أسباب الإلغاء دون التأثير على أي استعلامات تشغيلية أخرى.
     */
    public function scopeCancelledOnly(Builder $query): Builder
    {
        return $query->onlyTrashed()->whereNotNull('cancellation_reason');
    }

    /**
     * يوم فاصل إلزامي بين أي حجزين على نفس الغرفة — يخرج النزيل ويُترك يومٌ
     * كامل لأعمال النظافة والتجهيز قبل وصول النزيل التالي (لا تناوب في نفس اليوم).
     * تغيير هذا الثابت وحده يضبط حجم الفاصل في كل مكان.
     */
    public const TURNOVER_BUFFER_DAYS = 1;

    /**
     * الغرف التي يشغلها هذا الحجز فعلياً (الغرفة الرئيسية + المرتبطة عند الجناح
     * الكامل A+B أو الشقة) — تُستخدم لفحص تداخل الحجوزات على أي منها.
     */
    public function occupiedRoomIds(): array
    {
        return array_values(array_filter([$this->room_id, $this->linked_room_id]));
    }

    /**
     * أول حجز نشط يتعارض مع الفترة [checkIn, checkOut) على إحدى الغرف المُمرَّرة،
     * مع مراعاة يوم فاصل للتنظيف (TURNOVER_BUFFER_DAYS) بين الحجزين.
     *
     * يُعدّ حجزان متعارضين إذا لم يفصل بينهما اليومُ الفاصل كاملاً — أي:
     *   (their_in < out + buffer) و (their_out > in − buffer)
     * وبفاصل يوم واحد يعني ذلك أن خروج النزيل يجب أن يسبق وصول النزيل القادم بيوم
     * على الأقل (مثال: قادم يصل 17/7 ⇐ أقصى خروج مسموح 16/7).
     *
     * نطابق على room_id أو linked_room_id حتى نلتقط حجوزات الجناح الكامل التي
     * تشغل الغرفة كقسمٍ مرتبط. ونتجاهل حجزاً بعينه (لعمليات التجديد/التعديل).
     */
    public static function findOverlap(array $roomIds, $checkIn, $checkOut, ?int $excludeId = null): ?self
    {
        $roomIds = array_values(array_filter($roomIds));
        if (empty($roomIds)) {
            return null;
        }

        $buffer  = self::TURNOVER_BUFFER_DAYS;
        // نوسّع فترة الحجز المطلوب بمقدار الفاصل على كلا الطرفين، ثم نطبّق منطق
        // التداخل نصف المفتوح المعتاد — فيُحجَب أي حجز لا يفصله يومٌ كامل.
        $outPlus = \Carbon\Carbon::parse($checkOut)->addDays($buffer)->toDateString();
        $inMinus = \Carbon\Carbon::parse($checkIn)->subDays($buffer)->toDateString();

        return static::query()
            ->with('guest')
            ->where('status', 'checked_in')
            ->when($excludeId, fn (Builder $q) => $q->where('id', '!=', $excludeId))
            ->where(function (Builder $q) use ($roomIds) {
                $q->whereIn('room_id', $roomIds)->orWhereIn('linked_room_id', $roomIds);
            })
            ->whereDate('check_in_date', '<', $outPlus)
            ->whereDate('check_out_date', '>', $inMinus)
            ->orderBy('check_in_date')
            ->first();
    }

    /**
     * أقصى تاريخ خروج مسموح به لحجز/تجديد على إحدى الغرف — تاريخ وصول أقرب نزيل
     * قادم ناقص اليوم الفاصل (فيُترك يوم للتنظيف قبل وصوله). يُرجَع Carbon أو null
     * إن لم يوجد حجز قادم يقيّد المدة.
     */
    public static function maxCheckoutBefore(array $roomIds, $checkIn, ?int $excludeId = null): ?\Carbon\Carbon
    {
        $next = self::nextCheckInAfter($roomIds, $checkIn, $excludeId);
        return $next?->copy()->subDays(self::TURNOVER_BUFFER_DAYS);
    }

    /**
     * أقرب تاريخ وصول لحجزٍ نشط يبدأ بعد التاريخ المُعطى على إحدى الغرف. يُرجَع
     * كـ Carbon أو null إن لم يوجد حجز قادم.
     */
    public static function nextCheckInAfter(array $roomIds, $checkIn, ?int $excludeId = null): ?\Carbon\Carbon
    {
        $roomIds = array_values(array_filter($roomIds));
        if (empty($roomIds)) {
            return null;
        }

        $after = \Carbon\Carbon::parse($checkIn)->toDateString();

        $date = static::query()
            ->where('status', 'checked_in')
            ->when($excludeId, fn (Builder $q) => $q->where('id', '!=', $excludeId))
            ->where(function (Builder $q) use ($roomIds) {
                $q->whereIn('room_id', $roomIds)->orWhereIn('linked_room_id', $roomIds);
            })
            ->whereDate('check_in_date', '>', $after)
            ->min('check_in_date');

        return $date ? \Carbon\Carbon::parse($date) : null;
    }

    public function getBalanceAttribute(): float
    {
        return (float)$this->total_amount - (float)$this->paid_amount;
    }

    public function getNightsAttribute(): int
    {
        return $this->check_in_date->diffInDays($this->check_out_date);
    }

    /**
     * سعر الليلة عند التجديد/التمديد — قد يختلف عن سعر الليلة الأولى المتفاوَض
     * عليه (مثال: 35 ألف لليوم الأول مقابل 40 ألف لكل ليلة إضافية عند التجديد).
     * إن لم يُحدَّد صراحةً (null) نستخدم نفس منطق سعر الليلة الأولى كما كان سابقاً.
     */
    public function getEffectiveRenewalPricePerNightAttribute(): float
    {
        if ($this->renewal_price_per_night !== null) {
            return (float) $this->renewal_price_per_night;
        }

        return $this->nights > 0
            ? round((float) $this->total_amount / $this->nights, 2)
            : (float) $this->total_amount;
    }

    public function getDisplayRoomNumberAttribute(): string
    {
        if (!$this->room) {
            return '—';
        }
        // عند حجز الجناح كاملاً (A+B): اعرض الرقم بدون الحرف (301 بدلاً من 301A)
        if ($this->suite_booking_type === 'both') {
            return rtrim($this->room->room_number, 'AB');
        }
        return $this->room->room_number;
    }

    /**
     * تصنيف الغرفة كما يظهر للنزيل/في التقارير — يعكس النوع الفعلي
     * (عادية/زوجية/جناح/شقة/صالة) بدل اسم فئة التسعير العام.
     */
    public function getRoomTypeLabelAttribute(): string
    {
        if (!$this->room) {
            return '—';
        }
        // جناح محجوز بالكامل A+B
        if ($this->suite_booking_type === 'both') {
            return 'جناح كامل (A+B)';
        }
        return $this->room->sub_type_label;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'confirmed'  => 'محجوز',
            'checked_in' => 'مسجل دخول',
            'checked_out' => 'مسجل خروج',
            'cancelled'  => 'ملغى',
            default => $this->status,
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'unpaid' => 'غير مدفوع',
            'partial' => 'جزئي',
            'paid' => 'مدفوع',
            'deferred' => 'مؤجل',
            default => $this->payment_status,
        };
    }

    public function updatePaymentStatus(): void
    {
        $status = 'unpaid';
        if ((float)$this->total_amount == 0) {
            $status = 'paid';
        } elseif ($this->paid_amount >= $this->total_amount) {
            $status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $status = 'partial';
        } elseif ($this->payment_status === 'deferred') {
            $status = 'deferred';
        }
        $this->update(['payment_status' => $status]);
    }
}
