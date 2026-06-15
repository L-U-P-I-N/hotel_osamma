<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name', 'employee_id', 'username', 'password', 'phone', 'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'created_by');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'received_by');
    }

    public function cashSettlements()
    {
        return $this->hasMany(CashSettlement::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
