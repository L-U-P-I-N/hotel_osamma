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
        'hotel_id','room_type_id','room_number','floor',
        'room_sub_type','linked_room_id','is_always_linked',
        'status','notes',
    ];

    protected $casts = [
        'is_always_linked' => 'boolean',
    ];

    public function hotel()      { return $this->belongsTo(Hotel::class); }
    public function roomType()   { return $this->belongsTo(RoomType::class); }
    public function reservations(){ return $this->hasMany(Reservation::class); }
    public function linkedRoom() { return $this->belongsTo(Room::class, 'linked_room_id'); }

    // --- scopes ---

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'available');
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    // --- helpers ---

    public function isSuiteA(): bool   { return $this->room_sub_type === 'suite_a'; }
    public function isSuiteB(): bool   { return $this->room_sub_type === 'suite_b'; }
    public function isSuite(): bool    { return in_array($this->room_sub_type, ['suite_a','suite_b']); }
    public function isApartment(): bool{ return $this->room_sub_type === 'apartment'; }
    public function isHall(): bool     { return $this->room_sub_type === 'hall'; }
    public function isDouble(): bool   { return $this->room_sub_type === 'double'; }

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
