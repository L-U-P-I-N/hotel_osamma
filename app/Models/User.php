<?php
namespace App\Models;

use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $fillable = [
        'name','employee_id','username','password','phone','is_active','backup_code',
    ];

    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // --- relationships ---

    public function reservations()    { return $this->hasMany(Reservation::class, 'created_by'); }
    public function payments()        { return $this->hasMany(Payment::class, 'received_by'); }
    public function cashSettlements() { return $this->hasMany(CashSettlement::class); }
    public function shifts()          { return $this->hasMany(Shift::class); }
    public function auditLogs()       { return $this->hasMany(AuditLog::class); }
    public function userPermissions() { return $this->hasMany(UserPermission::class); }
    public function employee()        { return $this->belongsTo(\App\Models\Employee::class); }

    // --- role helpers ---

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isReceptionist(): bool
    {
        return $this->hasRole('receptionist');
    }

    // --- permission override: replaces Spatie check with our dynamic system ---

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $key = is_string($permission) ? $permission : $permission->name;
        return PermissionService::userCan($this, $key);
    }
}
