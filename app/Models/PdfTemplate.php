<?php
namespace App\Models;

use App\Support\ReportRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * قالب تصدير PDF يصمّمه المدير بنفسه.
 *
 * القوالب المخزَّنة هنا ليست Blade ولا PHP: هي HTML مع عناصر نائبة
 * تُعالَج بمحرّك خاص (TemplateRenderer) لا ينفّذ أي كود. السبب أن تنفيذ
 * نصّ مخزَّن في قاعدة البيانات يعني أن من يسرق كلمة مرور المدير ينفّذ ما
 * يشاء على خادم الفندق.
 */
class PdfTemplate extends Model
{
    protected $fillable = [
        'name', 'report_key', 'doc_title', 'body', 'is_default', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForReport($query, string $reportKey)
    {
        return $query->where('report_key', $reportKey);
    }

    public function getReportLabelAttribute(): string
    {
        return ReportRegistry::label($this->report_key);
    }

    /**
     * تعيين هذا القالب افتراضياً لتقريره — وإلغاء افتراضية غيره،
     * فلا يصحّ وجود افتراضيَّين لنفس التقرير.
     */
    public function makeDefault(): void
    {
        static::forReport($this->report_key)->where('id', '!=', $this->id)->update(['is_default' => false]);

        $this->forceFill(['is_default' => true, 'is_active' => true])->save();
    }

    /** القوالب المتاحة للاختيار عند تصدير تقرير معيّن. */
    public static function choicesFor(string $reportKey)
    {
        if (ReportRegistry::isLocked($reportKey)) {
            return collect();
        }

        return static::active()->forReport($reportKey)->orderByDesc('is_default')->orderBy('name')->get();
    }
}
