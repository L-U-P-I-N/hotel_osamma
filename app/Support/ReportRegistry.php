<?php
namespace App\Support;

/**
 * فهرس تقارير النظام وصلاحية كل تقرير.
 *
 * كانت صلاحية واحدة (reports.view) تفتح كل التقارير، فيرى موظف الاستقبال
 * الأرباح والرواتب والصندوق العام. التقارير هنا مقسَّمة بحسب من يعنيه
 * التقرير فعلاً، ولكلٍّ صلاحيته المستقلة التي يمنحها المدير.
 */
class ReportRegistry
{
    public const GROUP_FRONTDESK = 'الاستقبال';
    public const GROUP_ADMIN     = 'الإدارة';
    public const GROUP_FINANCE   = 'المالية';

    /**
     * المفتاح => [العنوان، الصلاحية، الفئة، المسار].
     * 'locked' يعني قالب تصديره ثابت لا يقبل تخصيص التصميم.
     */
    public const REPORTS = [
        // ── الاستقبال ──
        'daily' => [
            'label' => 'التقرير اليومي', 'permission' => 'reports.daily',
            'group' => self::GROUP_FRONTDESK, 'route' => 'reports.dailyHub',
        ],
        'rooms_inventory' => [
            'label' => 'جرد غرف اليومية', 'permission' => 'reports.rooms_inventory',
            'group' => self::GROUP_FRONTDESK, 'route' => 'reports.amAli', 'locked' => true,
        ],
        'guests_rooms' => [
            'label' => 'النزلاء والغرف', 'permission' => 'reports.guests_rooms',
            'group' => self::GROUP_FRONTDESK, 'route' => 'reports.guestsRoomsHub',
        ],
        'reservations' => [
            'label' => 'تقرير الحجوزات', 'permission' => 'reports.reservations',
            'group' => self::GROUP_FRONTDESK, 'route' => null, 'locked' => true,
        ],
        'government' => [
            'label' => 'التصدير للجهات الحكومية', 'permission' => 'government.export',
            'group' => self::GROUP_FRONTDESK, 'route' => 'reports.government', 'locked' => true,
        ],

        // ── الإدارة ──
        'occupancy' => [
            'label' => 'تقرير الإشغال', 'permission' => 'reports.occupancy',
            'group' => self::GROUP_ADMIN, 'route' => null,
        ],
        'cancelled' => [
            'label' => 'الحجوزات الملغاة', 'permission' => 'reports.cancelled',
            'group' => self::GROUP_ADMIN, 'route' => 'reports.cancelledReservations',
        ],
        'shifts' => [
            'label' => 'تقارير الورديات', 'permission' => 'reports.shifts',
            'group' => self::GROUP_ADMIN, 'route' => 'reports.shiftsHub',
        ],
        'staff' => [
            'label' => 'أداء الموظفين', 'permission' => 'reports.staff',
            'group' => self::GROUP_ADMIN, 'route' => null,
        ],
        'hr' => [
            'label' => 'تقارير الموارد البشرية', 'permission' => 'reports.hr',
            'group' => self::GROUP_ADMIN, 'route' => 'reports.hrHub',
        ],
        'integrity' => [
            'label' => 'فحص تكامل البيانات', 'permission' => 'reports.integrity',
            'group' => self::GROUP_ADMIN, 'route' => 'reports.financialIntegrity',
        ],

        // ── المالية ──
        'revenue' => [
            'label' => 'تقرير الإيرادات', 'permission' => 'reports.revenue',
            'group' => self::GROUP_FINANCE, 'route' => null,
        ],
        'finance' => [
            'label' => 'الملخص المالي', 'permission' => 'reports.finance',
            'group' => self::GROUP_FINANCE, 'route' => 'reports.financeHub',
        ],
        'profit_loss' => [
            'label' => 'الأرباح والخسائر', 'permission' => 'reports.profit_loss',
            'group' => self::GROUP_FINANCE, 'route' => 'reports.profitLoss',
        ],
        'debts' => [
            'label' => 'تقرير الديون', 'permission' => 'reports.debts',
            'group' => self::GROUP_FINANCE, 'route' => 'reports.debts',
        ],
        'partial_payments' => [
            'label' => 'الدفعات الجزئية', 'permission' => 'reports.partial_payments',
            'group' => self::GROUP_FINANCE, 'route' => 'reports.partialPayments',
        ],
        'general_safe' => [
            'label' => 'الصندوق العام', 'permission' => 'reports.general_safe',
            'group' => self::GROUP_FINANCE, 'route' => 'reports.generalSafe',
        ],
        'account_search' => [
            'label' => 'البحث في الحسابات', 'permission' => 'reports.account_search',
            'group' => self::GROUP_FINANCE, 'route' => 'reports.accountSearch',
        ],
        'salaries' => [
            'label' => 'تقرير الرواتب', 'permission' => 'reports.salaries',
            'group' => self::GROUP_FINANCE, 'route' => null,
        ],
    ];

    /** صلاحيات التقارير لإدراجها في شاشة الصلاحيات. */
    public static function permissions(): array
    {
        $permissions = [];

        foreach (self::REPORTS as $report) {
            // التصدير الحكومي له صلاحيته القديمة المعرَّفة أصلاً، فلا نكرّرها
            if ($report['permission'] === 'government.export') {
                continue;
            }

            $permissions[$report['permission']] = [
                'label'   => $report['label'],
                'default' => false,
                'group'   => '📊 تقارير ' . $report['group'],
            ];
        }

        return $permissions;
    }

    /** التقارير المسموح بتخصيص قالب تصديرها (غير الثابتة). */
    public static function customisable(): array
    {
        return array_filter(self::REPORTS, fn ($r) => empty($r['locked']));
    }

    public static function label(string $key): string
    {
        return self::REPORTS[$key]['label'] ?? $key;
    }

    public static function isLocked(string $key): bool
    {
        return !empty(self::REPORTS[$key]['locked']);
    }
}
