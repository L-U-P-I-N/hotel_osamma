<?php
namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permissions): mixed
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        try {
            foreach (explode('|', $permissions) as $perm) {
                if (PermissionService::userCan($user, trim($perm))) {
                    return $next($request);
                }
            }
        } catch (\Throwable $e) {
            Log::error('CheckPermission error: ' . $e->getMessage(), [
                'user_id'     => $user->id,
                'permissions' => $permissions,
                'file'        => $e->getFile(),
                'line'        => $e->getLine(),
            ]);
            // إذا كان الأدمن (role=admin) اسمح له مباشرة تجنباً لقطع الوصول
            if ($user->getRoleNames()->contains('admin')) {
                return $next($request);
            }
            return $this->deny($request, 'تعذّر التحقق من صلاحياتك بسبب خطأ في النظام. أعد المحاولة، وإن تكرر الأمر راجع الدعم الفني.');
        }

        return $this->deny($request, 'ليست لديك صلاحية لهذه العملية — راجع مدير النظام لمنحك الصلاحية.');
    }

    /**
     * رفض الوصول مع إظهار إشعار خطأ بدل صفحة 403 كاملة.
     * - طلبات AJAX/JSON: استجابة JSON بحالة 403.
     * - الطلبات العادية: إعادة توجيه للصفحة السابقة مع رسالة خطأ منبثقة.
     */
    private function deny(Request $request, string $message): mixed
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => false, 'message' => $message], 403);
        }

        $previous = url()->previous();
        $target = ($previous && $previous !== url()->current())
            ? $previous
            : route('dashboard');

        return redirect($target)->with('error', $message);
    }
}
