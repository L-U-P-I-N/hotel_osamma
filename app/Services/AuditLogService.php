<?php
namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogService
{
    public static function log(
        string $action,
        ?Model $model = null,
        ?array $old = null,
        ?array $new = null,
        ?User $user = null
    ): void {
        $request = request();
        AuditLog::create([
            'user_id' => $user?->id ?? auth()->id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $old,
            'new_values' => $new,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
