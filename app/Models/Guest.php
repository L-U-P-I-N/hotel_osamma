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

    /**
     * إقامات سابقة غادر منها النزيل دون سداد كامل المبلغ — أي دَينٌ قديم عليه.
     * تُستثنى الإقامة الحالية (إن مُرِّر رقمها) والحجوزات الملغاة، ويُقتصر على
     * "غادر" فرصيد إقامة قائمة ليس ديناً سابقاً بل مبلغاً مستحقاً بعد.
     */
    public function unpaidPreviousStays(?int $excludeReservationId = null)
    {
        return $this->reservations()
            ->where('status', 'checked_out')
            ->whereColumn('paid_amount', '<', 'total_amount')
            ->when($excludeReservationId, fn($q) => $q->where('id', '!=', $excludeReservationId))
            ->orderBy('check_out_date');
    }

    /** إجمالي الدَّين القديم على النزيل من إقاماته السابقة. */
    public function previousDebtTotal(?int $excludeReservationId = null): float
    {
        return round((float) $this->unpaidPreviousStays($excludeReservationId)
            ->selectRaw('COALESCE(SUM(total_amount - paid_amount), 0) as debt')
            ->value('debt'), 2);
    }

    public function scopeSearchByIdNumber(Builder $query, string $number): Builder
    {
        return $query->where('id_number_hash', hash('sha256', $number));
    }

    /**
     * أنواع الهوية المقبولة — مصدرٌ واحد تقرأ منه النماذج وقواعد التحقق
     * والتسميات وسجل المراجعة.
     *
     * كانت القائمة مكرّرة في ستة مواضع (موديلين، ثلاثة متحكّمات، عدة نماذج)،
     * فإضافة نوعٍ واحد تقتضي تتبّعها كلها — ويكفي أن يُنسى موضع ليُرفض ما
     * يختاره الموظف أو يظهر كوداً خاماً في التقرير.
     */
    public const ID_TYPES = [
        'national_id'       => 'هوية وطنية',
        'passport'          => 'جواز سفر',
        'residence'         => 'إقامة',
        'driver_license'    => 'رخصة قيادة',
        'birth_certificate' => 'شهادة ميلاد',
        'military_id'       => 'بطاقة عسكرية',
    ];

    /**
     * تسميات مختصرة لأعمدة التقارير الضيقة (PDF وExcel) — التسمية الكاملة
     * تلفّ السطر فيها فيختلّ الجدول.
     */
    public const ID_TYPES_SHORT = [
        'national_id'       => 'بطاقة',
        'passport'          => 'جواز',
        'residence'         => 'إقامة',
        'driver_license'    => 'رخصة',
        'birth_certificate' => 'ميلاد',
        'military_id'       => 'عسكرية',
    ];

    /** قاعدة التحقق: in:national_id,passport,... */
    public static function idTypeRule(): string
    {
        return 'in:' . implode(',', array_keys(self::ID_TYPES));
    }

    public function getIdTypeLabel(): string
    {
        return self::ID_TYPES[$this->id_type] ?? ($this->id_type ?: '—');
    }
}
