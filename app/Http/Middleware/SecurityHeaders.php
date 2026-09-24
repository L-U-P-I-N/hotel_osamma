<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ترويسات حماية على كل استجابة — تغلق ثغرات تُستغلّ بسكربتات جاهزة:
 *
 *  • frame-ancestors / X-Frame-Options: تمنع وضع النظام داخل إطار في موقع
 *    مزيَّف يلتقط ضغطات الموظف أو بيانات دخوله (clickjacking).
 *  • nosniff: يمنع المتصفح من "تخمين" نوع ملف مرفوع وتشغيله كسكربت.
 *  • Referrer-Policy: لا تُسرَّب روابط النظام (وفيها أرقام الحجوزات) لمواقع خارجية.
 *  • Permissions-Policy: تُغلق الكاميرا والميكروفون والموقع — لا يحتاجها النظام.
 *  • HSTS: يُلزم المتصفح بـHTTPS فلا تُسرَق الجلسة على شبكة عامة (يُضاف على
 *    الاتصال الآمن فقط كي لا تتعطّل التجربة محلياً).
 *  • إزالة X-Powered-By: لا نُعلن إصدار PHP للماسحات الآلية.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        // النظام يُحمّل سكربتات من CDN ويستعمل سكربتات مضمَّنة، فنقتصر هنا على
        // منع التأطير — وهو الجزء الذي لا يكسر شيئاً ويُغلق هجوماً حقيقياً.
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'");

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
