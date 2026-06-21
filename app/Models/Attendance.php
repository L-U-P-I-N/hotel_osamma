<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id', 'date', 'status', 'check_in', 'check_out', 'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            'present' => 'حاضر',
            'absent'  => 'غائب',
            'late'    => 'متأخر',
            'leave'   => 'إجازة',
            'holiday' => 'عطلة',
            default   => $status,
        };
    }

    public static function statusColor(string $status): string
    {
        return match($status) {
            'present' => 'bg-green-100 text-green-800',
            'absent'  => 'bg-red-100 text-red-800',
            'late'    => 'bg-yellow-100 text-yellow-800',
            'leave'   => 'bg-blue-100 text-blue-800',
            'holiday' => 'bg-gray-100 text-gray-600',
            default   => 'bg-gray-100 text-gray-600',
        };
    }
}
