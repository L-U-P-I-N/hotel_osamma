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
        'nights', 'price_per_night', 'amount', 'created_by',
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

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'renewal' ? 'تجديد' : 'الحجز الأولي';
    }
}
