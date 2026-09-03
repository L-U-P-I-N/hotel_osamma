<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['hotel_id', 'name', 'base_price', 'min_price', 'max_price', 'suite_min_price', 'suite_max_price', 'max_capacity', 'description'];

    protected $casts = [
        'base_price'   => 'decimal:2',
        'min_price'    => 'decimal:2',
        'max_price'    => 'decimal:2',
        'suite_min_price' => 'decimal:2',
        'suite_max_price' => 'decimal:2',
        'max_capacity' => 'integer',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    /**
     * الحد الأدنى لسعر الليلة. غياب الإعداد يسقط على السعر الأساسي بدل أن
     * يتحول إلى نطاق مفتوح — النطاق المفتوح هو الثغرة التي نغلقها هنا.
     */
    public function getEffectiveMinPriceAttribute(): float
    {
        $min = (float) $this->min_price;
        return $min > 0 ? $min : (float) $this->base_price;
    }

    public function getEffectiveMaxPriceAttribute(): float
    {
        $max = (float) $this->max_price;
        $min = $this->effective_min_price;
        // نطاق مقلوب أو غير مضبوط يُعامل كنطاق مغلق على الحد الأدنى
        return $max >= $min ? $max : $min;
    }

    /** هل النطاق مضبوط فعلاً (وليس مجرد سقوط على السعر الأساسي)؟ */
    public function hasExplicitBounds(): bool
    {
        return (float) $this->min_price > 0 && (float) $this->max_price > 0;
    }

    // --- نطاق الجناح كاملاً (غرفتان) ---
    // مستقل عن نطاق القسم: الجناح قد يُباع بسعر عرض أقل من مجموع قسميه أو أعلى.
    // ما لم يضبطه المدير يسقط على ضعف نطاق القسم.

    public function hasExplicitSuiteBounds(): bool
    {
        return (float) $this->suite_min_price > 0 && (float) $this->suite_max_price > 0;
    }

    public function getEffectiveSuiteMinPriceAttribute(): float
    {
        $min = (float) $this->suite_min_price;
        return $min > 0 ? $min : round($this->effective_min_price * 2, 2);
    }

    public function getEffectiveSuiteMaxPriceAttribute(): float
    {
        // غير مضبوط (صفر) => ضِعف أعلى سعر للقسم، لا الحدّ الأدنى للجناح،
        // وإلا انكمش النطاق إلى نقطة واحدة عند غياب الإعداد.
        $max = (float) $this->suite_max_price;
        if ($max <= 0) {
            $max = round($this->effective_max_price * 2, 2);
        }

        $min = $this->effective_suite_min_price;
        return $max >= $min ? $max : $min;
    }

    public function isSuitePriceWithinBounds(float $price): bool
    {
        return $price >= $this->effective_suite_min_price
            && $price <= $this->effective_suite_max_price;
    }
}
