<?php
namespace App\Http\Middleware;

use App\Support\TemplateResolver;
use Closure;
use Illuminate\Http\Request;

/**
 * التقاط القالب الذي اختاره المستخدم لهذا التصدير (?template=).
 *
 * وسيط واحد بدل تمرير المُعرّف يدوياً عبر كل دالة تصدير؛ ويُصفَّر بعد
 * الاستجابة كي لا يتسرّب الاختيار إلى طلب آخر في نفس العملية (octane/queue).
 */
class SelectPdfTemplate
{
    public function handle(Request $request, Closure $next): mixed
    {
        // template=0 تعني «تصميم النظام الأصلي» صراحةً، وهي غير غياب المعامل
        $raw           = $request->query('template');
        $forceOriginal = $raw !== null && (int) $raw === 0;
        $chosen        = $forceOriginal ? null : ((int) $raw ?: null);

        TemplateResolver::choose($chosen, $forceOriginal);

        try {
            return $next($request);
        } finally {
            TemplateResolver::choose(null, false);
        }
    }
}
