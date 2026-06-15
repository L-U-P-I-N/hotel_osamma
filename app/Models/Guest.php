<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Guest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name', 'nationality', 'occupation', 'id_type', 'id_number',
        'id_issuer', 'id_issue_date', 'phone', 'id_image_path',
        'is_blacklisted', 'blacklist_reason', 'blacklisted_at',
    ];

    protected $casts = [
        'id_number' => 'encrypted',
        'phone' => 'encrypted',
        'is_blacklisted' => 'boolean',
        'id_issue_date' => 'date',
        'blacklisted_at' => 'datetime',
    ];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeBlacklisted(Builder $query): Builder
    {
        return $query->where('is_blacklisted', true);
    }

    public function scopeSearchByIdNumber(Builder $query, string $number): Builder
    {
        return $query->where('id_number', $number);
    }

    public function getIdTypeLabel(): string
    {
        return match($this->id_type) {
            'passport' => 'جواز سفر',
            'national_id' => 'هوية وطنية',
            'residence' => 'إقامة',
            default => $this->id_type,
        };
    }
}
