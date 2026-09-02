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
        'pricing.manage',
    ];

    // صلاحيات مفعّلة افتراضياً للموظف
    const RECEPTIONIST_DEFAULTS = [
        'dashboard.view',
        'rooms.view',
        'rooms.maintenance',
        'checkin.create',
        'checkin.view',
        'reservation.edit',
        'reservation.renew',
        'checkout.process',
        'payments.create',
        'shifts.view',
        'settlement.view', // backward compat alias
        'reports.view',
    ];

    // جميع الصلاحيات القابلة للتعديل
    const ALL_PERMISSIONS = [
        'checkin.create'        => ['label' => 'إضافة حجز جديد',             'default' => true],
        'checkin.view'          => ['label' => 'عرض الحجوزات وتفاصيلها',     'default' => true],
        'reservation.edit'      => ['label' => 'تعديل الحجز',                 'default' => true],
        'reservation.renew'     => ['label' => 'تجديد الإقامة',               'default' => true],
        'reservation.cancel'    => ['label' => 'إلغاء الحجز',                 'default' => false],
        'reservation.discount'  => ['label' => 'منح خصم على الحجز',           'default' => false],
        'checkout.process'      => ['label' => 'تسجيل الخروج',               'default' => true],
        'payments.create'       => ['label' => 'تسجيل المستلمات',             'default' => true],
        'shifts.view'           => ['label' => 'عرض الوردية',                 'default' => true],
        'withdrawal.create'     => ['label' => 'تسجيل السحبيات',              'default' => true],
        'reports.view'          => ['label' => 'عرض التقارير',                'default' => true],
        'guests.sensitive'      => ['label' => 'عرض الهوية ورقم الجوال',     'default' => false],
        'guest.edit'            => ['label' => 'تعديل بيانات النزيل',         'default' => false],
        'room.price.edit'       => ['label' => 'تعديل سعر الليلة (ضمن النطاق المحدد)', 'default' => false],
        'payments.bank_receipt' => ['label' => 'عرض سندات التحويل',           'default' => false],
        'blacklist.manage'      => ['label' => 'إدارة القائمة السوداء',       'default' => false],
        'government.export'     => ['label' => 'التصدير للجهات الحكومية',    'default' => false],
        'report.monthly'        => ['label' => 'التقرير الشهري',              'default' => false],
        'rooms.maintenance'     => ['label' => 'تغيير حالة الغرفة (صيانة/فحص/متاحة)', 'default' => true],
    ];

    // أزرار تفاصيل الحجز — كلها تتطلب أيضاً صلاحية عرض الحجوزات
    const RESERVATION_ACTION_KEYS = [
        'reservation.edit'     => true,
        'reservation.renew'    => true,
        'reservation.cancel'   => true,
        'reservation.discount' => true,
    ];

    /** كاش لكل طلب: صفحة تفاصيل الحجز وحدها تفحص عشرات الصلاحيات */
    private static array $cache = [];

    public static function userCan(User $user, string $permission): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (in_array($permission, self::ADMIN_ONLY)) {
            return false;
        }

        // كل أزرار تفاصيل الحجز مشروطة أيضاً بصلاحية عرض الحجوزات،
        // فلا معنى لتعديل أو إلغاء حجز لا يستطيع الموظف رؤيته أصلاً.
        if (isset(self::RESERVATION_ACTION_KEYS[$permission])
            && !self::userCan($user, 'checkin.view')) {
            return false;
        }

        // array_key_exists وليس isset — القيمة false نتيجة صالحة يجب أن تُقرأ من الكاش
        if (array_key_exists($permission, self::$cache[$user->id] ?? [])) {
            return self::$cache[$user->id][$permission];
        }

        // Check explicit record
        $record = UserPermission::where('user_id', $user->id)
            ->where('permission_key', $permission)
            ->first();

        if ($record !== null) {
            $allowed = (bool) $record->is_granted;
        } elseif ($permission === 'settlement.view') {
            // settlement.view is alias for shifts.view
            $allowed = self::userCan($user, 'shifts.view');
        } else {
            $allowed = in_array($permission, self::RECEPTIONIST_DEFAULTS);
        }

        return self::$cache[$user->id][$permission] = $allowed;
    }

    public static function toggle(User $user, string $key, bool $grant, User $by): void
    {
        UserPermission::updateOrCreate(
            ['user_id' => $user->id, 'permission_key' => $key],
            ['is_granted' => $grant, 'granted_by' => $by->id, 'granted_at' => now()]
        );

        unset(self::$cache[$user->id]);
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
