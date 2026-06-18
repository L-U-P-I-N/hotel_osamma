<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;

    protected $table = 'leaves';

    protected $fillable = [
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'reason',
        'status',
        'reviewed_by',
        'review_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'annual'    => 'سنوية',
            'sick'      => 'مرضية',
            'emergency' => 'طارئة',
            default     => $type,
        };
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending'  => 'قيد المراجعة',
            'approved' => 'موافق عليها',
            'rejected' => 'مرفوضة',
            default    => $status,
        };
    }

    public function getDaysCountAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }
}
