<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RoomInspection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reservation_id', 'inspected_by', 'has_damage', 'damage_description',
        'compensation_amount', 'compensation_status', 'inspection_date',
    ];

    protected $casts = [
        'has_damage' => 'boolean',
        'compensation_amount' => 'decimal:2',
        'inspection_date' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function images()
    {
        return $this->hasMany(InspectionImage::class);
    }
}
