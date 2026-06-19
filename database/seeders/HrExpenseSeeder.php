<?php
namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Salary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HrExpenseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::role('admin')->first();
        $adminId = $admin?->id ?? 1;

        // ===== الموظفون =====
        $employees = [
            ['name' => 'محمد أحمد الزهراني',   'national_id' => '1092345678', 'position' => 'مدير الاستقبال', 'base_salary' => 3500000, 'phone' => '0501234567', 'hire_date' => '2022-03-15'],
            ['name' => 'عبدالله سالم القحطاني', 'national_id' => '1087654321', 'position' => 'موظف استقبال',  'base_salary' => 2200000, 'phone' => '0507654321', 'hire_date' => '2023-01-10'],
            ['name' => 'خالد عمر العمري',       'national_id' => '1076543210', 'position' => 'موظف استقبال',  'base_salary' => 2200000, 'phone' => '0509876543', 'hire_date' => '2023-06-01'],
            ['name' => 'يوسف علي الشهري',       'national_id' => '1065432109', 'position' => 'محاسب',         'base_salary' => 3000000, 'phone' => '0503456789', 'hire_date' => '2022-09-20'],
            ['name' => 'فهد ناصر الدوسري',      'national_id' => '1054321098', 'position' => 'مشرف النظافة', 'base_salary' => 1800000, 'phone' => '0506789012', 'hire_date' => '2021-11-05'],
            ['name' => 'سعد مطلق الرشيدي',      'national_id' => '1043210987', 'position' => 'عامل نظافة',   'base_salary' => 1400000, 'phone' => '0508901234', 'hire_date' => '2023-03-15'],
            ['name' => 'حمد جابر الحربي',       'national_id' => '1032109876', 'position' => 'عامل نظافة',   'base_salary' => 1400000, 'phone' => '0502345678', 'hire_date' => '2023-08-01'],
            ['name' => 'طارق وليد المالكي',     'national_id' => '1021098765', 'position' => 'فني صيانة',    'base_salary' => 2500000, 'phone' => '0504567890', 'hire_date' => '2022-05-12'],
        ];

        $createdEmployees = [];
        foreach ($employees as $data) {
            $emp = Employee::firstOrCreate(
                ['national_id' => $data['national_id']],
                array_merge($data, ['is_active' => true])
            );
            $createdEmployees[] = $emp;
        }

        // ===== الرواتب (3 أشهر) =====
        $now = Carbon::now();
        foreach ($createdEmployees as $emp) {
            for ($m = 2; $m >= 0; $m--) {
                $date   = $now->copy()->subMonths($m);
                $month  = (int) $date->format('m');
                $year   = (int) $date->format('Y');
                $isPaid = $m > 0;

                $bonus = [0, 100000, 200000, 300000][array_rand([0, 100000, 200000, 300000])];
                $net   = $emp->base_salary + $bonus;

                Salary::firstOrCreate(
                    ['employee_id' => $emp->id, 'month' => $month, 'year' => $year],
                    [
                        'base_salary' => $emp->base_salary,
                        'bonuses'     => $bonus,
                        'deductions'  => 0,
                        'net_salary'  => $net,
                        'status'      => $isPaid ? 'paid' : 'draft',
                        'created_by'  => $adminId,
                    ]
                );
            }
        }

        // ===== المصروفات =====
        $expenses = [
            ['category' => 'electricity', 'amount' => 1850000, 'currency' => 'YER', 'description' => 'فاتورة الكهرباء والمياه — الشهر الماضي',     'paid_by_name' => 'محمد الزهراني',   'days_ago' => 32],
            ['category' => 'electricity', 'amount' => 1920000, 'currency' => 'YER', 'description' => 'فاتورة الكهرباء والمياه — الشهر الحالي',     'paid_by_name' => 'يوسف الشهري',     'days_ago' => 2],
            ['category' => 'cleaning',    'amount' => 320000,  'currency' => 'YER', 'description' => 'مواد تنظيف وأدوات صحية — الأسبوع الرابع',   'paid_by_name' => 'فهد الدوسري',     'days_ago' => 28],
            ['category' => 'cleaning',    'amount' => 310000,  'currency' => 'YER', 'description' => 'مواد تنظيف وأدوات صحية — الأسبوع الثالث',   'paid_by_name' => 'فهد الدوسري',     'days_ago' => 21],
            ['category' => 'cleaning',    'amount' => 335000,  'currency' => 'YER', 'description' => 'مواد تنظيف وأدوات صحية — الأسبوع الثاني',   'paid_by_name' => 'فهد الدوسري',     'days_ago' => 14],
            ['category' => 'cleaning',    'amount' => 290000,  'currency' => 'YER', 'description' => 'مواد تنظيف وأدوات صحية — هذا الأسبوع',      'paid_by_name' => 'فهد الدوسري',     'days_ago' => 7],
            ['category' => 'maintenance', 'amount' => 750000,  'currency' => 'YER', 'description' => 'إصلاح تكييف غرف الطابق الثاني',              'paid_by_name' => 'طارق المالكي',    'days_ago' => 35],
            ['category' => 'maintenance', 'amount' => 420000,  'currency' => 'YER', 'description' => 'تصليح تسرب مياه في الحمام 305',              'paid_by_name' => 'طارق المالكي',    'days_ago' => 18],
            ['category' => 'maintenance', 'amount' => 210000,  'currency' => 'YER', 'description' => 'استبدال مفاتيح كهربائية وإضاءة',             'paid_by_name' => 'طارق المالكي',    'days_ago' => 5],
            ['category' => 'maintenance', 'amount' => 1200000, 'currency' => 'YER', 'description' => 'صيانة دورية للمصعد',                          'paid_by_name' => 'طارق المالكي',    'days_ago' => 40],
            ['category' => 'food',        'amount' => 180000,  'currency' => 'YER', 'description' => 'وجبات الموظفين — الأسبوع الماضي',            'paid_by_name' => 'عبدالله القحطاني','days_ago' => 8],
            ['category' => 'food',        'amount' => 165000,  'currency' => 'YER', 'description' => 'وجبات الموظفين — هذا الأسبوع',               'paid_by_name' => 'عبدالله القحطاني','days_ago' => 1],
            ['category' => 'food',        'amount' => 95000,   'currency' => 'YER', 'description' => 'ضيافة وقهوة لاجتماع الإدارة',                'paid_by_name' => 'محمد الزهراني',   'days_ago' => 12],
            ['category' => 'salary',      'amount' => 18000000,'currency' => 'YER', 'description' => 'صرف رواتب الموظفين — الشهر الماضي',          'paid_by_name' => 'يوسف الشهري',     'days_ago' => 30],
            ['category' => 'other',       'amount' => 2800000, 'currency' => 'YER', 'description' => 'شراء 4 مراتب جديدة للغرف',                   'paid_by_name' => 'محمد الزهراني',   'days_ago' => 44],
            ['category' => 'other',       'amount' => 450000,  'currency' => 'YER', 'description' => 'لوازم مكتبية وأدوات الاستقبال',              'paid_by_name' => 'خالد العمري',     'days_ago' => 22],
            ['category' => 'other',       'amount' => 120000,  'currency' => 'YER', 'description' => 'تجديد بطاقات الغرف (Key Cards)',              'paid_by_name' => 'خالد العمري',     'days_ago' => 10],
            ['category' => 'other',       'amount' => 900000,  'currency' => 'YER', 'description' => 'رسوم خدمة الأمن والحراسة — الشهر الماضي',   'paid_by_name' => 'يوسف الشهري',     'days_ago' => 31],
            ['category' => 'other',       'amount' => 900000,  'currency' => 'YER', 'description' => 'رسوم خدمة الأمن والحراسة — الشهر الحالي',   'paid_by_name' => 'يوسف الشهري',     'days_ago' => 1],
            ['category' => 'maintenance', 'amount' => 850,     'currency' => 'SAR', 'description' => 'قطع غيار تكييف — طلب خارجي',                'paid_by_name' => 'طارق المالكي',    'days_ago' => 15],
            ['category' => 'other',       'amount' => 1200,    'currency' => 'SAR', 'description' => 'شراء أجهزة كمبيوتر للاستقبال',               'paid_by_name' => 'محمد الزهراني',   'days_ago' => 38],
        ];

        foreach ($expenses as $t) {
            Expense::create([
                'amount'       => $t['amount'],
                'currency'     => $t['currency'],
                'category'     => $t['category'],
                'description'  => $t['description'],
                'expense_date' => now()->subDays($t['days_ago'])->toDateString(),
                'paid_by'      => $adminId,
                'shift_id'     => null,
            ]);
        }
    }
}
