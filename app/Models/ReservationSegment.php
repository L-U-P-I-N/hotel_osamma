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
        'reservation_id', 'type', 'start_date', 'end_date',
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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'renewal' ? 'تجديد' : 'الحجز الأولي';
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
