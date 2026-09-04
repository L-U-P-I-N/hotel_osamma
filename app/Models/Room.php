<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'hotel_id','room_type_id','room_number','floor','beds_count',
        'price_yer','suite_price_yer','price_sar','price_usd',
        'room_sub_type','linked_room_id','is_always_linked',
        'status','notes',
    ];

    protected $casts = [
        'is_always_linked' => 'boolean',
        'price_yer'       => 'decimal:2',
        'suite_price_yer' => 'decimal:2',
        'price_sar'       => 'decimal:2',
        'price_usd'       => 'decimal:2',
    ];

    public function hotel()      { return $this->belongsTo(Hotel::class); }
    public function roomType()   { return $this->belongsTo(RoomType::class); }
    public function reservations(){ return $this->hasMany(Reservation::class); }
    public function linkedRoom() { return $this->belongsTo(Room::class, 'linked_room_id'); }

    // --- mutators ---

    public function setRoomNumberAttribute(string $value): void
    {
        $this->attributes['room_number'] = self::normalizeDigits($value);
    }

    public static function normalizeDigits(string $value): string
    {
        return strtr(trim($value), [
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4',
            '٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
            // Persian digits
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4',
            '۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        ]);
    }

    // --- scopes ---

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // --- pricing ---

    /**
     * Price for a given currency. YER falls back to the room type's base_price
     * when not explicitly set on the room (backwards compatibility).
     */
    public function priceFor(string $currency): float
    {
        $currency = strtoupper($currency);
        $value = match ($currency) {
            'SAR'   => $this->price_sar,
            'USD'   => $this->price_usd,
            default => $this->price_yer,
        };

        if (($value === null || $value === '') && $currency === 'YER') {
            return (float) ($this->roomType?->base_price ?? 0);
        }

        return (float) ($value ?? 0);
    }

    public function pricesArray(): array
    {
        return [
            'YER'       => $this->priceFor('YER'),
            'SAR'       => $this->priceFor('SAR'),
            'USD'       => $this->priceFor('USD'),
            'SUITE_YER' => (float)($this->suite_price_yer ?? 0),
        ];
    }

    public static function currencySymbol(string $currency): string
    {
        return match (strtoupper($currency)) {
            'SAR'   => 'ر.س',
            'USD'   => '$',
            default => 'ر.ي',
        };
    }

    // --- helpers ---

    public function isSuiteA(): bool   { return $this->room_sub_type === 'suite_a'; }
    public function isSuiteB(): bool   { return $this->room_sub_type === 'suite_b'; }
    public function isSuite(): bool    { return in_array($this->room_sub_type, ['suite_a','suite_b']); }
    public function isApartment(): bool{ return $this->room_sub_type === 'apartment'; }
    public function isHall(): bool     { return $this->room_sub_type === 'hall'; }
    public function isDouble(): bool   { return $this->room_sub_type === 'double'; }

    /**
     * القسم المقابل للجناح (301A ↔ 301B) — عبر الربط المباشر، وإلا عبر رقم الغرفة.
     */
    public function suitePartner(): ?Room
    {
        if ($this->linkedRoom) {
            return $this->linkedRoom;
        }
        if (!$this->isSuite()) {
            return null;
        }
        $base = rtrim($this->room_number, 'ABab');
        $pairNumber = $base . ($this->isSuiteA() ? 'B' : 'A');
        return static::where('room_number', $pairNumber)
            ->whereIn('room_sub_type', ['suite_a', 'suite_b'])
            ->first();
    }

    /**
     * سعر الجناح كاملاً (A+B) لليلة الواحدة بالريال اليمني.
     * يُؤخذ من suite_price_yer لهذا القسم أو للقسم المقابل،
     * وإلا يُحتسب كمجموع سعرَي القسمين.
     */
    public function fullSuitePrice(): float
    {
        // الجناح غرفتان: سعره مجموع سعرَي قسميه، لا سعر مستقل يُدخَل يدوياً.
        // (عمود suite_price_yer لم يعد يُقرأ — بقي في القاعدة للسجل التاريخي فقط،
        //  فوجود تسعيرة ثانية للجناح هو ما كان يفتح باب التلاعب.)
        $partner = $this->suitePartner();

        return round($this->priceFor('YER') + ($partner?->priceFor('YER') ?? $this->priceFor('YER')), 2);
    }

    public function isLinkedRoomAvailable(): bool
    {
        if (!$this->linked_room_id) return false;
        $linked = $this->linkedRoom;
        return $linked && $linked->status === 'available';
    }

    // --- labels ---

    public function getSubTypeLabelAttribute(): string
    {
        return match($this->room_sub_type) {
            'suite_a'   => 'جناح A',
            'suite_b'   => 'جناح B',
            'apartment' => 'شقة',
            'hall'      => 'صالة',
            'double'    => 'غرفة زوجية',
            default     => 'غرفة عادية',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'available'       => 'متاحة',
            'reserved'        => 'محجوزة',
            'occupied'        => 'مشغولة',
            'under_inspection'=> 'تحت الفحص',
            'maintenance'     => 'صيانة',
            default           => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'available'        => 'green',
            'reserved'         => 'blue',
            'occupied'         => 'red',
            'under_inspection' => 'yellow',
            'maintenance'      => 'gray',
            default            => 'gray',
        };
    }
}
