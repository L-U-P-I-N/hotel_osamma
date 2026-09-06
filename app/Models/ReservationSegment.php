<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * فترة محاسبة واحدة للغرفة: إمّا الحجز الأولي (type=initial) أو تجديد (type=renewal).
 * تسجّل تاريخ البداية/النهاية وعدد الليالي وسعر الليلة والمبلغ، لعرض تفصيل واضح
 * لأسعار الغرفة بدل متوسط الليلة.
 */
class ReservationSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id', 'room_id', 'type', 'start_date', 'end_date',
        'nights', 'price_per_night', 'amount', 'created_by', 'shift_id',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'nights'          => 'integer',
        'price_per_night' => 'decimal:2',
        'amount'          => 'decimal:2',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * "تجديد" في القاعدة قد يكون تمديداً فعلياً لنفس الغرفة، أو استمراراً بعد
     * نقل النزيل لغرفة أخرى (لم نُضِف نوعاً جديداً في القاعدة تفادياً لتعقيد
     * تعديل enum عبر محرّكات قواعد بيانات مختلفة) — فنميّز التسمية بمقارنة
     * غرفة هذه الفترة بغرفة الفترة التي قبلها مباشرة.
     */
    public function getTypeLabelAttribute(): string
    {
        if ($this->type !== 'renewal') {
            return 'الحجز الأولي';
        }

        return $this->isRoomChange() ? 'تغيير غرفة' : 'تجديد';
    }

    /** هل غرفة هذه الفترة مختلفة عن غرفة الفترة السابقة لها مباشرة؟ */
    public function isRoomChange(): bool
    {
        if ($this->room_id === null) {
            return false;
        }

        $previous = static::where('reservation_id', $this->reservation_id)
            ->where('start_date', '<', $this->start_date)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        return $previous !== null && $previous->room_id !== null && $previous->room_id !== $this->room_id;
    }

    /**
     * هل التعديل/الحذف على هذه الفترة مسموح؟ يُمنَع إذا كانت مرتبطة بوردية
     * أُقفلت بالفعل — فلا نُغيّر أرقام حجزٍ يخصّ عمل وردية سابقة منتهية ومُصفّاة.
     */
    public function isLocked(): bool
    {
        return $this->shift_id !== null && (bool) $this->shift?->is_closed;
    }

    /**
     * القفل المحاسبي يبقى قائماً، لكنه ليس نهائياً: من يملك صلاحية
     * segment.unlock (المدير افتراضياً) يستطيع تصحيح سعر أُدخل خطأً
     * في وردية أُقفلت، ويُسجَّل التجاوز في سجل المراجعة.
     */
    public function isEditableBy(?User $user): bool
    {
        if (!$this->isLocked()) {
            return true;
        }

        return $user !== null && $user->can('segment.unlock');
    }
}
