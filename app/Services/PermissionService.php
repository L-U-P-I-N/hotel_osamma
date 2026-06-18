<?php
namespace App\Services;

use App\Models\User;
use App\Models\UserPermission;

class PermissionService
{
    // صلاحيات للمدير فقط — لا يمكن منحها للموظفين
    const ADMIN_ONLY = [
        'users.manage',
        'audit_log.view',
        'rooms.manage',
    ];

    // صلاحيات مفعّلة افتراضياً للموظف
    const RECEPTIONIST_DEFAULTS = [
        'dashboard.view',
        'rooms.view',
        'rooms.maintenance',
        'checkin.create',
        'checkin.view',
        'checkout.process',
        'payments.create',
        'shifts.view',
        'settlement.view', // backward compat alias
        'reports.view',
    ];

    // جميع الصلاحيات القابلة للتعديل
    const ALL_PERMISSIONS = [
        'checkin.create'        => ['label' => 'إضافة حجز جديد',             'default' => true],
        'checkin.view'          => ['label' => 'عرض الحجوزات',                'default' => true],
        'checkout.process'      => ['label' => 'تسجيل الخروج',               'default' => true],
        'payments.create'       => ['label' => 'تسجيل المستلمات',             'default' => true],
        'shifts.view'           => ['label' => 'عرض الوردية',                 'default' => true],
        'withdrawal.create'     => ['label' => 'تسجيل السحبيات',              'default' => true],
        'reports.view'          => ['label' => 'عرض التقارير',                'default' => true],
        'guests.sensitive'      => ['label' => 'عرض الهوية ورقم الجوال',     'default' => false],
        'guest.edit'            => ['label' => 'تعديل بيانات النزيل',         'default' => false],
        'room.price.edit'       => ['label' => 'تعديل سعر الغرفة',           'default' => false],
        'payments.bank_receipt' => ['label' => 'عرض سندات التحويل',           'default' => false],
        'government.export'     => ['label' => 'التصدير للجهات الحكومية',    'default' => false],
        'report.monthly'        => ['label' => 'التقرير الشهري',              'default' => false],
        'rooms.maintenance'     => ['label' => 'تغيير حالة الغرفة (صيانة/فحص/متاحة)', 'default' => true],
        // HR Module
        'hr.view'               => ['label' => 'عرض الموارد البشرية (موظفون، رواتب، إجازات)', 'default' => false],
        'hr.manage'             => ['label' => 'إدارة الموارد البشرية (إضافة وتعديل)',          'default' => false],
        // Expense Module
        'expenses.view'         => ['label' => 'عرض المصروفات والموردين',                        'default' => false],
        'expenses.manage'       => ['label' => 'إدارة المصروفات (إضافة وتعديل وحذف)',            'default' => false],
    ];

    public static function userCan(User $user, string $permission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (in_array($permission, self::ADMIN_ONLY)) {
            return false;
        }

        // Check explicit record
        try {
            $record = UserPermission::where('user_id', $user->id)
                ->where('permission_key', $permission)
                ->first();

            if ($record !== null) {
                return $record->is_granted;
            }
        } catch (\Throwable $e) {
            // جدول user_permissions غير موجود أو خطأ في DB — نرجع للقيمة الافتراضية
        }

        // settlement.view is alias for shifts.view
        if ($permission === 'settlement.view') {
            return self::userCan($user, 'shifts.view');
        }

        return in_array($permission, self::RECEPTIONIST_DEFAULTS);
    }

    public static function toggle(User $user, string $key, bool $grant, User $by): void
    {
        UserPermission::updateOrCreate(
            ['user_id' => $user->id, 'permission_key' => $key],
            ['is_granted' => $grant, 'granted_by' => $by->id, 'granted_at' => now()]
        );
    }

    public static function getMap(User $user): array
    {
        if ($user->isAdmin()) {
            return []; // admin has all, no need to display
        }

        $stored = UserPermission::where('user_id', $user->id)
            ->pluck('is_granted', 'permission_key')
            ->toArray();

        $map = [];
        foreach (self::ALL_PERMISSIONS as $key => $config) {
            $map[$key] = [
                'label'     => $config['label'],
                'is_granted'=> $stored[$key] ?? $config['default'],
                'default'   => $config['default'],
                'is_custom' => isset($stored[$key]),
            ];
        }
        return $map;
    }
}
