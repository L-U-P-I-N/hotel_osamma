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
    ];

    protected $casts = [
        'id_number'     => 'encrypted',
        'phone'         => 'encrypted',
        'id_issue_date' => 'date',
    ];

    protected static function booted(): void
    {
        // id_number مُشفَّر بتشفير غير حتمي، فلا يمكن البحث عنه مباشرة بـ WHERE.
        // نحسب بصمة (hash) حتمية عند كل حفظ لتبقى قابلة للمطابقة الدقيقة لاحقاً.
        static::saving(function (Guest $guest) {
            if ($guest->isDirty('id_number')) {
                $guest->id_number_hash = $guest->id_number ? hash('sha256', $guest->id_number) : null;
            }
        });
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function scopeSearchByIdNumber(Builder $query, string $number): Builder
    {
        return $query->where('id_number_hash', hash('sha256', $number));
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
