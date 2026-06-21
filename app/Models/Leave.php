<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $fillable = [
        'employee_id', 'type', 'from_date', 'to_date', 'days', 'notes', 'created_by',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function typeLabel(string $type): string
    {
        return match($type) {
            'annual'    => 'سنوية',
            'sick'      => 'مرضية',
            'emergency' => 'طارئة',
            'unpaid'    => 'بدون راتب',
            default     => $type,
        };
    }
}
