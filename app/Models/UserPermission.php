<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    protected $fillable = ['user_id','permission_key','is_granted','granted_by','granted_at'];

    protected $casts = [
        'is_granted'  => 'boolean',
        'granted_at'  => 'datetime',
    ];

    public function user()      { return $this->belongsTo(User::class); }
    public function grantedBy() { return $this->belongsTo(User::class, 'granted_by'); }
}
