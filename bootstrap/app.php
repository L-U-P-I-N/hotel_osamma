<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
        $middleware->appendToGroup('web', \App\Http\Middleware\NoCacheHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // انتهاء صلاحية الجلسة/رمز CSRF (419): يحدث عندما تبقى صفحة النموذج مفتوحة
        // طويلاً (مثل تسجيل نزيل مع رفع صور) — كان الطلب يُرفض بصفحة صمّاء دون سبب،
        // فيظن الموظف أن النظام "لم يحفظ بلا سبب" وتنجح المحاولة الثانية لأن الرمز يتجدد.
        // نعيده الآن إلى النموذج برسالة واضحة مع الاحتفاظ بالمدخلات.
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return null;
            }
            return back()
                ->withInput(\Illuminate\Support\Arr::except($request->input(), ['_token', 'password', 'password_confirmation']))
                ->withErrors(['error' => 'انتهت صلاحية الجلسة لبقاء الصفحة مفتوحة مدة طويلة، ولم يُحفظ أي شيء — أعد الضغط على زر الحفظ الآن وسيتم التسجيل.']);
        });

        // حجم الملفات المرفوعة تجاوز حد الخادم — كانت تظهر صفحة خطأ عامة دون توضيح
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return null;
            }
            return back()->withErrors([
                'error' => 'تعذّر الحفظ: حجم الصور/الملفات المرفوعة أكبر من الحد المسموح به. قلّل حجم الصور أو عددها ثم أعد المحاولة.',
            ]);
        });
    })->create();
