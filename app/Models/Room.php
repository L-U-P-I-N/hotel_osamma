<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Room extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['hotel_id', 'room_type_id', 'room_number', 'floor', 'status', 'notes'];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'available' => 'متاحة',
            'reserved' => 'محجوزة',
            'occupied' => 'مشغولة',
            'under_inspection' => 'تحت الفحص',
            'maintenance' => 'صيانة',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'available' => 'green',
            'reserved' => 'blue',
            'occupied' => 'red',
            'under_inspection' => 'yellow',
            'maintenance' => 'gray',
            default => 'gray',
        };
    }
}
