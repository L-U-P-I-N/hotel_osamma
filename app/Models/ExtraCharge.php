<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExtraCharge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reservation_id', 'room_inspection_id', 'added_by', 'type', 'description', 'amount', 'charge_date',
        'in_hotel_total', 'settled_at', 'settled_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charge_date' => 'datetime',
        'in_hotel_total' => 'boolean',
        'settled_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function settledBy()
    {
        return $this->belongsTo(User::class, 'settled_by');
    }

    /**
     * رسوم المشتريات (بقالة/خدمات النزيل) المنفصلة عن صندوق الفندق — أي رسم لا
     * يُحتسب ضمن إجمالي الحجز.
     */
    public function scopePurchases($query)
    {
        return $query->where('in_hotel_total', false);
    }

    /** الرسوم المحتسبة ضمن إجمالي الفندق (أضرار + رسوم قديمة). */
    public function scopeHotel($query)
    {
        return $query->where('in_hotel_total', true);
    }

    /** دَين مشتريات غير مُحصَّل (لم يُسلَّم للبقالة بعد). */
    public function scopeOutstanding($query)
    {
        return $query->where('in_hotel_total', false)->whereNull('settled_at');
    }

    public function getIsSettledAttribute(): bool
    {
        return $this->settled_at !== null;
    }
}
