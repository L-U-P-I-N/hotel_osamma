<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id','payment_id','processed_by','shift_id',
        'amount','currency','method','reason','refunded_at','notes',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'refunded_at' => 'datetime',
    ];

    public function reservation() { return $this->belongsTo(Reservation::class); }
    public function payment()     { return $this->belongsTo(Payment::class); }
    public function processedBy() { return $this->belongsTo(User::class, 'processed_by'); }
    public function shift()       { return $this->belongsTo(Shift::class); }
}
