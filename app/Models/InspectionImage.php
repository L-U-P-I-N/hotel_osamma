<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InspectionImage extends Model
{
    use HasFactory;

    protected $fillable = ['room_inspection_id', 'image_path'];

    public function inspection()
    {
        return $this->belongsTo(RoomInspection::class, 'room_inspection_id');
    }
}
