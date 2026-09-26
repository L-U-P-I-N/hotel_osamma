<?php
namespace App\Support;

/**
 * تنقية كود القالب الملصَق قبل حفظه.
 *
 * المدير يلصق HTML جاهزاً من أي مكان، وقد يحمل ما لا يقصده: سكربتات،
 * وسم PHP، أو طلب لخادم خارجي يسرّب بيانات النزلاء عبر رابط صورة.
 * نرفض ما يمكن أن يضرّ ونبقي ما يخدم التصميم.
 */
class TemplateSanitizer
{
    /** وسوم تُحذف بمحتواها كاملاً. */
    private const FORBIDDEN_TAGS = [
        'script', 'iframe', 'object', 'embed', 'applet', 'form', 'input',
        'button', 'textarea', 'select', 'link', 'meta', 'base', 'frame', 'frameset',
    ];

    /** أنماط تُرفض ويُبلَّغ عنها المدير بدل حذفها بصمت. */
    private const REJECTED = [
        '/<\?(php|=)/i'                    => 'وسم PHP غير مسموح داخل القالب.',
        '/\{\{--/'                         => 'تعليقات Blade غير مسموحة — استعمل تعليق HTML.',
        '/@(php|include|extends|inject)\b/i'=> 'أوامر Blade غير مسموحة داخل القالب.',
        '/javascript\s*:/i'                => 'روابط javascript: غير مسموحة.',
        '/\bon[a-z]+\s*=/i'                => 'خصائص الأحداث (onclick وأخواتها) غير مسموحة.',
        '/<script\b/i'                     => 'وسم <script> غير مسموح.',
        '/<iframe\b/i'                     => 'وسم <iframe> غير مسموح.',
        '/@import\b/i'                     => 'استيراد ملفات أنماط خارجية غير مسموح.',
        '/url\s*\(\s*["\']?\s*https?:/i'    => 'لا يُسمح بجلب صور أو خطوط من خادم خارجي داخل التصدير.',
        '/<(link|base|meta)\b/i'           => 'وسوم الترويسة (link/base/meta) غير مسموحة.',
    ];

    /** حجم القالب الأقصى — قالب أكبر من ذلك يُرهق توليد الملف. */
    private const MAX_BYTES = 200000;

    /**
     * أسباب رفض القالب. مصفوفة فارغة تعني أنه مقبول.
     *
     * @return string[]
     */
    public static function problems(string $body): array
    {
        $problems = [];

        if (strlen($body) > self::MAX_BYTES) {
            $problems[] = 'حجم القالب أكبر من المسموح (' . number_format(self::MAX_BYTES / 1000) . ' كيلوبايت). اختصر التصميم.';
        }

        foreach (self::REJECTED as $pattern => $reason) {
            if (preg_match($pattern, $body)) {
                $problems[] = $reason;
            }
        }

        // صور خارجية تسرّب البيانات لمن يستضيف الصورة عند كل تصدير
        if (preg_match('/<img[^>]+src\s*=\s*["\']?\s*https?:/i', $body)) {
            $problems[] = 'لا يُسمح بصورة من رابط خارجي — ارفع الشعار من إعدادات الفندق واستعمل {{ شعار_الفندق }}.';
        }

        return array_values(array_unique($problems));
    }

    /**
     * إزالة ما تبقّى من وسوم ممنوعة كطبقة ثانية بعد الرفض.
     * الرفض هو الحارس الأول؛ هذه شبكة أمان عند الحفظ.
     */
    public static function clean(string $body): string
    {
        foreach (self::FORBIDDEN_TAGS as $tag) {
            $body = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $body) ?? $body;
            $body = preg_replace('/<\/?' . $tag . '\b[^>]*>/i', '', $body) ?? $body;
        }

        // خصائص الأحداث وروابط javascript:
        $body = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $body) ?? $body;
        $body = preg_replace('/javascript\s*:/i', '', $body) ?? $body;

        return trim($body);
    }
}
