<?php
namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permissions): mixed
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Support pipe-separated OR logic: "rooms.manage|rooms.maintenance"
        foreach (explode('|', $permissions) as $perm) {
            if (PermissionService::userCan($user, trim($perm))) {
                return $next($request);
            }
        }

        abort(403, 'غير مصرح لك بهذا الإجراء');
    }
}
