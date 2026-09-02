<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['hotel_id', 'name', 'base_price', 'min_price', 'max_price', 'max_capacity', 'description'];

    protected $casts = [
        'base_price'   => 'decimal:2',
        'min_price'    => 'decimal:2',
        'max_price'    => 'decimal:2',
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
     * الحد الأدنى المسموح للسعر الليلي. يسقط على السعر الأساسي إذا لم يحدده المدير
     * حتى لا يتحول غياب الإعداد إلى نطاق مفتوح.
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
        // نطاق غير صالح (أقصى أقل من أدنى) يُعامل كنطاق مغلق على الأدنى
        return $max >= $min ? $max : $min;
    }

    public function isPriceWithinBounds(float $price): bool
    {
        return $price >= $this->effective_min_price && $price <= $this->effective_max_price;
    }
}
