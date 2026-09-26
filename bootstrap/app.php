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
        // التقاط قالب التصدير المختار (?template=) لكل طلب على حدة
        $middleware->appendToGroup('web', \App\Http\Middleware\SelectPdfTemplate::class);
        // ترويسات حماية على كل استجابة (منع التأطير، منع تخمين نوع الملفات، …)
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
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

        // قاعدة البيانات ترفض العملية: قيود المفاتيح والحقول الإلزامية والتكرار.
        // كانت تصل للموظف كنصّ SQL إنجليزي طويل داخل صفحة خطأ عامة.
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            $message = \App\Support\FriendlyError::forDatabase($e);

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()
                ->withInput(\Illuminate\Support\Arr::except($request->input(), ['_token', 'password', 'password_confirmation']))
                ->withErrors(['error' => $message]);
        });

        // سجلّ غير موجود (حُذف أو رابط قديم): كانت تظهر صفحة 404 إنجليزية
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            $message = 'السجل المطلوب غير موجود — قد يكون محذوفاً أو أن الرابط قديم.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 404);
            }

            return response()->view('errors.404', ['friendly' => $message], 404);
        });

        // صلاحية ناقصة: الموظف يحتاج أن يعرف أن عليه مراجعة المدير، لا كلمة "Forbidden"
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException $e, \Illuminate\Http\Request $request) {
            $message = trim($e->getMessage()) ?: 'ليست لديك صلاحية لهذه العملية — راجع مدير النظام لمنحك الصلاحية.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return response()->view('errors.403', ['friendly' => $message], 403);
        });

        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            $message = trim($e->getMessage()) ?: 'ليست لديك صلاحية لهذه العملية — راجع مدير النظام لمنحك الصلاحية.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return response()->view('errors.403', ['friendly' => $message], 403);
        });

        // كثرة الطلبات (حماية تسجيل الدخول مثلاً)
        $exceptions->render(function (\Illuminate\Http\Exceptions\ThrottleRequestsException $e, \Illuminate\Http\Request $request) {
            $seconds = (int) ($e->getHeaders()['Retry-After'] ?? 60);
            $message  = 'محاولات كثيرة في وقت قصير. انتظر ' . \App\Support\FriendlyError::duration($seconds) . ' ثم أعد المحاولة.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 429);
            }

            return back()->withErrors(['error' => $message]);
        });

        // الجلسة انتهت والمستخدم لم يعد مسجّل الدخول
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            $message = 'انتهت جلستك أو تم تسجيل دخولك من جهاز آخر — سجّل الدخول من جديد للمتابعة.';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 401);
            }

            return redirect()->guest(route('login'))->withErrors(['username' => $message]);
        });
    })->create();
