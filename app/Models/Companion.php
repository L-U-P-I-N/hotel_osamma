<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Companion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reservation_id', 'full_name', 'nationality', 'id_type', 'id_number',
        'id_issuer', 'id_issue_date', 'relationship', 'id_image_path', 'marriage_doc_path',
    ];

    protected $casts = [
        'id_number' => 'encrypted',
        'id_issue_date' => 'date',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function getIdTypeLabel(): string
    {
        return match($this->id_type) {
            'passport'    => 'جواز سفر',
            'national_id' => 'هوية وطنية',
            'residence'   => 'إقامة',
            default       => $this->id_type ?? '—',
        };
    }

    public function getRelationshipLabel(): string
    {
        return match($this->relationship) {
            'wife' => 'زوجة',
            'son' => 'ابن',
            'daughter' => 'ابنة',
            'brother' => 'أخ',
            'sister' => 'أخت',
            'father' => 'أب',
            'mother' => 'أم',
            'other' => 'أخرى',
            default => $this->relationship,
        };
    }
}
