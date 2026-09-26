<?php

use App\Models\UserPermission;
use App\Support\ReportRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * التقارير صار لكل واحد منها صلاحية مستقلة، وكلها تبدأ ممنوعة.
 * من كان يملك reports.view قبل هذا التغيير كان يرى كل التقارير فعلاً،
 * فنمنحه إياها صراحةً كي لا يفقد وصولاً كان يستعمله، ثم يضيّقها المدير
 * من شاشة الصلاحيات كما يشاء.
 */
return new class extends Migration
{
    public function up(): void
    {
        $viewerIds = UserPermission::where('permission_key', 'reports.view')
            ->where('is_granted', true)
            ->pluck('user_id')
            ->unique();

        if ($viewerIds->isEmpty()) {
            return;
        }

        $keys = collect(ReportRegistry::REPORTS)->pluck('permission')->unique();
        $now  = now();
        $rows = [];

        foreach ($viewerIds as $userId) {
            foreach ($keys as $key) {
                $rows[] = [
                    'user_id'        => $userId,
                    'permission_key' => $key,
                    'is_granted'     => true,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }
        }

        // لا نلمس صلاحية ضبطها المدير يدوياً من قبل
        $existing = UserPermission::whereIn('user_id', $viewerIds)
            ->whereIn('permission_key', $keys)
            ->get()
            ->map(fn ($p) => $p->user_id . '|' . $p->permission_key)
            ->flip();

        $rows = array_values(array_filter(
            $rows,
            fn ($row) => !$existing->has($row['user_id'] . '|' . $row['permission_key'])
        ));

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('user_permissions')->insert($chunk);
        }
    }

    public function down(): void
    {
        UserPermission::whereIn('permission_key', collect(ReportRegistry::REPORTS)->pluck('permission')->unique())
            ->delete();
    }
};
