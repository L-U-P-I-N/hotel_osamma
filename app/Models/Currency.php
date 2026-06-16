<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'name', 'symbol', 'code', 'exchange_rate_to_yer', 'is_primary', 'is_active',
    ];

    protected $casts = [
        'exchange_rate_to_yer' => 'decimal:2',
        'is_primary'           => 'boolean',
        'is_active'            => 'boolean',
    ];

    public function scopeActive($query)  { return $query->where('is_active', true); }
    public function scopePrimary($query) { return $query->where('is_primary', true); }
}
