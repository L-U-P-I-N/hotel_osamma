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
        'withdrawal.create',
        'shifts.view',
        'settlement.view', // backward compat alias
    ];

    // جميع الصلاحيات القابلة للتعديل
    const ALL_PERMISSIONS = [
        // Reservations & Check-in/out
        'checkin.create'        => ['label' => 'إضافة حجز جديد',             'default' => true,  'group' => '📋 الحجوزات والإقامة'],
        'checkin.view'          => ['label' => 'عرض الحجوزات',                'default' => true,  'group' => '📋 الحجوزات والإقامة'],
        'checkin.edit'          => ['label' => 'تعديل بيانات الحجز',          'default' => false, 'group' => '📋 الحجوزات والإقامة'],
        'checkin.delete'        => ['label' => 'حذف / إلغاء الحجز',           'default' => false, 'group' => '📋 الحجوزات والإقامة'],
        'checkout.process'      => ['label' => 'تسجيل الخروج',               'default' => true,  'group' => '📋 الحجوزات والإقامة'],

        // Guests
        'guests.sensitive'      => ['label' => 'عرض الهوية ورقم الجوال',     'default' => false, 'group' => '👤 النزلاء'],
        'guests.create'         => ['label' => 'إضافة نزيل جديد',             'default' => false, 'group' => '👤 النزلاء'],
        'guest.edit'            => ['label' => 'تعديل بيانات النزيل',         'default' => false, 'group' => '👤 النزلاء'],
        'guests.delete'         => ['label' => 'حذف سجل النزيل',              'default' => false, 'group' => '👤 النزلاء'],

        // Rooms
        'rooms.view'            => ['label' => 'عرض قائمة الغرف',                     'default' => true,  'group' => '🛏️ إدارة الغرف'],
        'rooms.create'          => ['label' => 'إضافة غرفة جديدة',                    'default' => false, 'group' => '🛏️ إدارة الغرف'],
        'rooms.edit'            => ['label' => 'تعديل بيانات الغرفة',                 'default' => false, 'group' => '🛏️ إدارة الغرف'],
        'rooms.delete'          => ['label' => 'حذف الغرفة',                          'default' => false, 'group' => '🛏️ إدارة الغرف'],
        'rooms.maintenance'     => ['label' => 'تغيير حالة الغرفة (صيانة/فحص/متاحة)', 'default' => true, 'group' => '🛏️ إدارة الغرف'],
        'room.price.edit'       => ['label' => 'تعديل سعر الغرفة',           'default' => false, 'group' => '🛏️ إدارة الغرف'],

        // Payments & Withdrawals
        'payments.bank_receipt' => ['label' => 'عرض سندات التحويل',           'default' => false, 'group' => '💰 المالية'],
        'payments.create'       => ['label' => 'تسجيل المستلمات',             'default' => true,  'group' => '💰 المالية'],
        'payments.edit'         => ['label' => 'تعديل المستلمات',             'default' => false, 'group' => '💰 المالية'],
        'payments.delete'       => ['label' => 'حذف المستلمات',               'default' => false, 'group' => '💰 المالية'],
        'withdrawal.create'     => ['label' => 'تسجيل السحبيات',              'default' => true,  'group' => '💰 المالية'],

        // Shifts & Settlement
        'shifts.view'           => ['label' => 'عرض الوردية',                    'default' => true,  'group' => '⏰ الورديات'],
        'shifts.create'         => ['label' => 'فتح وردية يدوياً',               'default' => false, 'group' => '⏰ الورديات'],
        'shifts.edit'           => ['label' => 'تعديل بيانات الوردية',           'default' => false, 'group' => '⏰ الورديات'],
        'shifts.delete'         => ['label' => 'حذف الوردية',                    'default' => false, 'group' => '⏰ الورديات'],
        'shifts.reopen'         => ['label' => 'فتح إقفال الوردية (إعادة فتح)', 'default' => false, 'group' => '⏰ الورديات'],

        // Reports
        'reports.view'          => ['label' => 'عرض التقارير',                'default' => false, 'group' => '📊 التقارير'],
        'report.monthly'        => ['label' => 'التقرير الشهري',              'default' => false, 'group' => '📊 التقارير'],
        'government.export'     => ['label' => 'التصدير للجهات الحكومية',    'default' => false, 'group' => '📊 التقارير'],

        // HR Module
        'hr.view'               => ['label' => 'عرض الموارد البشرية (موظفون، رواتب)', 'default' => false, 'group' => '👥 الموارد البشرية'],
        'hr.create'             => ['label' => 'إضافة موظف أو قسيمة راتب',            'default' => false, 'group' => '👥 الموارد البشرية'],
        'hr.edit'               => ['label' => 'تعديل بيانات الموظفين والرواتب',       'default' => false, 'group' => '👥 الموارد البشرية'],
        'hr.delete'             => ['label' => 'حذف الموظفين',                         'default' => false, 'group' => '👥 الموارد البشرية'],

        // Expense Module
        'expenses.view'         => ['label' => 'عرض المصروفات',                 'default' => false, 'group' => '💸 المصروفات'],
        'expenses.create'       => ['label' => 'إضافة مصروف جديد',             'default' => false, 'group' => '💸 المصروفات'],
        'expenses.edit'         => ['label' => 'تعديل المصروفات',               'default' => false, 'group' => '💸 المصروفات'],
        'expenses.delete'       => ['label' => 'حذف المصروفات',                 'default' => false, 'group' => '💸 المصروفات'],

        // Attendance
        'attendance.view'       => ['label' => 'عرض كشف الحضور والغياب',   'default' => false, 'group' => '✅ الحضور والغياب'],
        'attendance.create'     => ['label' => 'تسجيل الحضور اليومي',       'default' => false, 'group' => '✅ الحضور والغياب'],
        'attendance.edit'       => ['label' => 'تعديل سجل الحضور',          'default' => false, 'group' => '✅ الحضور والغياب'],
        'attendance.delete'     => ['label' => 'حذف سجل الحضور',            'default' => false, 'group' => '✅ الحضور والغياب'],
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
                'group'     => $config['group'] ?? 'عام',
            ];
        }
        return $map;
    }
}
