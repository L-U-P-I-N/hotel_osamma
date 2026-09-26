<?php
namespace App\Support;

use App\Models\PdfTemplate;
use Illuminate\Support\Facades\Schema;

/**
 * اختيار قالب التصدير: ما اختاره المستخدم في هذا الطلب، أو القالب
 * الافتراضي للتقرير، أو لا شيء فيُستعمل تصميم النظام الأصلي.
 */
class TemplateResolver
{
    /** قالب Blade لكل تقرير — الجسر بين اسم القالب ومفتاح التقرير. */
    private const VIEW_TO_REPORT = [
        'reports.daily_pdf'                  => 'daily',
        'reports.daily_close_pdf'            => 'daily',
        'reports.occupancy_pdf'              => 'occupancy',
        'reports.revenue_pdf'                => 'revenue',
        'reports.debts_pdf'                  => 'debts',
        'reports.debts_pdf_aged'             => 'debts',
        'reports.guests_pdf'                 => 'guests_rooms',
        'reports.general_safe_pdf'           => 'general_safe',
        'reports.cancelled_reservations_pdf' => 'cancelled',
        'reports.staff_pdf'                  => 'staff',
        'reports.salaries_pdf'               => 'salaries',
        'reports.shifts_pdf'                 => 'shifts',
    ];

    /** القالب المختار لهذا الطلب. */
    private static ?int $chosenId = null;

    /** طُلب صراحةً تصميم النظام الأصلي (template=0)، فيُتجاوَز الافتراضي. */
    private static bool $forceOriginal = false;

    public static function choose(?int $templateId, bool $forceOriginal = false): void
    {
        self::$chosenId     = $templateId;
        self::$forceOriginal = $forceOriginal;
    }

    public static function forView(string $view): ?PdfTemplate
    {
        $reportKey = self::VIEW_TO_REPORT[$view] ?? null;

        if (!$reportKey || ReportRegistry::isLocked($reportKey) || self::$forceOriginal) {
            return null;
        }

        // الجدول قد لا يكون مُرحَّلاً بعد على نسخة قديمة
        if (!Schema::hasTable('pdf_templates')) {
            return null;
        }

        if (self::$chosenId) {
            $chosen = PdfTemplate::active()->forReport($reportKey)->find(self::$chosenId);
            if ($chosen) {
                return $chosen;
            }
        }

        return PdfTemplate::active()->forReport($reportKey)->where('is_default', true)->first();
    }

    /** مفتاح التقرير لقالب Blade، أو null لقالب غير قابل للتخصيص. */
    public static function reportKeyFor(string $view): ?string
    {
        return self::VIEW_TO_REPORT[$view] ?? null;
    }
}
