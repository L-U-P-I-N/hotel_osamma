<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'guest_id','room_id','linked_room_id','suite_booking_type','created_by',
        'check_in_date','check_in_time','check_out_date','check_out_time','actual_check_out','origin','purpose','notes',
        'status','payment_status','total_amount','paid_amount','currency',
        'admin_approval_id','government_exported','government_exported_at',
        'discount_type','discount_value','discount_amount','discount_reason',
    ];

    public function getCurrencySymbolAttribute(): string
    {
        return match (strtoupper($this->currency ?? 'YER')) {
            'SAR'   => 'ر.س',
            'USD'   => '$',
            default => 'ر.ي',
        };
    }

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'actual_check_out' => 'datetime',
        'government_exported' => 'boolean',
        'government_exported_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function guest()
    {
        return $this->belongsTo(Guest::class)->withTrashed();
    }

    public function room()       { return $this->belongsTo(Room::class); }
    public function linkedRoom() { return $this->belongsTo(Room::class, 'linked_room_id'); }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adminApproval()
    {
        return $this->belongsTo(User::class, 'admin_approval_id');
    }

    public function companions()
    {
        return $this->hasMany(Companion::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function roomInspections()
    {
        return $this->hasMany(RoomInspection::class);
    }

    public function extraCharges()
    {
        return $this->hasMany(ExtraCharge::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'checked_in');
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('check_in_date', today());
    }

    public function scopeCheckedIn(Builder $query): Builder
    {
        return $query->where('status', 'checked_in');
    }

    public function getBalanceAttribute(): float
    {
        return (float)$this->total_amount - (float)$this->paid_amount;
    }

    public function getNightsAttribute(): int
    {
        return $this->check_in_date->diffInDays($this->check_out_date);
    }

    public function getDisplayRoomNumberAttribute(): string
    {
        if (!$this->room) {
            return '—';
        }
        // عند حجز الجناح كاملاً (A+B): اعرض الرقم بدون الحرف (301 بدلاً من 301A)
        if ($this->suite_booking_type === 'both') {
            return rtrim($this->room->room_number, 'AB');
        }
        return $this->room->room_number;
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'confirmed'  => 'محجوز',
            'checked_in' => 'مسجل دخول',
            'checked_out' => 'مسجل خروج',
            'cancelled'  => 'ملغى',
            default => $this->status,
        };
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return match($this->payment_status) {
            'unpaid' => 'غير مدفوع',
            'partial' => 'جزئي',
            'paid' => 'مدفوع',
            'deferred' => 'مؤجل',
            default => $this->payment_status,
        };
    }

    public function updatePaymentStatus(): void
    {
        $status = 'unpaid';
        if ((float)$this->total_amount == 0) {
            $status = 'paid';
        } elseif ($this->paid_amount >= $this->total_amount) {
            $status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $status = 'partial';
        } elseif ($this->payment_status === 'deferred') {
            $status = 'deferred';
        }
        $this->update(['payment_status' => $status]);
    }
}
