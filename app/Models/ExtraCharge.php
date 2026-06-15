<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExtraCharge extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reservation_id', 'added_by', 'type', 'description', 'amount', 'charge_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charge_date' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
