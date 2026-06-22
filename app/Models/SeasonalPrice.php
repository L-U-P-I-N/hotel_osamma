<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SeasonalPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_type_id','name','from_date','to_date',
        'price_mode','price_yer','multiplier','notes','created_by',
    ];

    protected $casts = [
        'from_date'  => 'date',
        'to_date'    => 'date',
        'price_yer'  => 'decimal:2',
        'multiplier' => 'decimal:2',
    ];

    public function roomType()  { return $this->belongsTo(RoomType::class); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }

    public function isActive(): bool
    {
        return now()->between($this->from_date, $this->to_date);
    }

    public function priceFor(Room $room): float
    {
        if ($this->price_mode === 'fixed') {
            return (float) ($this->price_yer ?? $room->priceFor('YER'));
        }
        return round($room->priceFor('YER') * (float)($this->multiplier ?? 1), 2);
    }
}
