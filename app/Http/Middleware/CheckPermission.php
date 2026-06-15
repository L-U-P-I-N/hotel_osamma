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
            abort(403, 'حدث خطأ في فحص الصلاحيات');
        }

        abort(403, 'غير مصرح لك بهذا الإجراء');
    }
}
