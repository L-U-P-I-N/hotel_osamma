<?php
namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Salary;
use App\Models\Supplier;
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
            ['name' => 'محمد أحمد الزهراني',  'national_id' => '1092345678', 'position' => 'مدير الاستقبال',    'base_salary' => 3500000, 'phone' => '0501234567', 'hire_date' => '2022-03-15'],
            ['name' => 'عبدالله سالم القحطاني','national_id' => '1087654321', 'position' => 'موظف استقبال',      'base_salary' => 2200000, 'phone' => '0507654321', 'hire_date' => '2023-01-10'],
            ['name' => 'خالد عمر العمري',      'national_id' => '1076543210', 'position' => 'موظف استقبال',      'base_salary' => 2200000, 'phone' => '0509876543', 'hire_date' => '2023-06-01'],
            ['name' => 'يوسف علي الشهري',      'national_id' => '1065432109', 'position' => 'محاسب',             'base_salary' => 3000000, 'phone' => '0503456789', 'hire_date' => '2022-09-20'],
            ['name' => 'فهد ناصر الدوسري',     'national_id' => '1054321098', 'position' => 'مشرف النظافة',      'base_salary' => 1800000, 'phone' => '0506789012', 'hire_date' => '2021-11-05'],
            ['name' => 'سعد مطلق الرشيدي',     'national_id' => '1043210987', 'position' => 'عامل نظافة',        'base_salary' => 1400000, 'phone' => '0508901234', 'hire_date' => '2023-03-15'],
            ['name' => 'حمد جابر الحربي',      'national_id' => '1032109876', 'position' => 'عامل نظافة',        'base_salary' => 1400000, 'phone' => '0502345678', 'hire_date' => '2023-08-01'],
            ['name' => 'طارق وليد المالكي',    'national_id' => '1021098765', 'position' => 'فني صيانة',         'base_salary' => 2500000, 'phone' => '0504567890', 'hire_date' => '2022-05-12'],
        ];

        $createdEmployees = [];
        foreach ($employees as $data) {
            $emp = Employee::firstOrCreate(
                ['national_id' => $data['national_id']],
                array_merge($data, ['is_active' => true])
            );
            $createdEmployees[] = $emp;
        }

        // ===== الرواتب (3 أشهر: الشهر الحالي + شهرين ماضيين) =====
        $now = Carbon::now();
        foreach ($createdEmployees as $emp) {
            for ($m = 2; $m >= 0; $m--) {
                $date   = $now->copy()->subMonths($m);
                $month  = (int) $date->format('m');
                $year   = (int) $date->format('Y');
                $isPaid = $m > 0; // الشهران الماضيان مدفوعان، الحالي مسودة

                // بونص عشوائي بين 0 و 300000 ريال
                $bonus     = [0, 100000, 200000, 300000][array_rand([0, 100000, 200000, 300000])];
                $deduction = 0;
                $net       = $emp->base_salary + $bonus - $deduction;

                Salary::firstOrCreate(
                    ['employee_id' => $emp->id, 'month' => $month, 'year' => $year],
                    [
                        'base_salary' => $emp->base_salary,
                        'bonuses'     => $bonus,
                        'deductions'  => $deduction,
                        'net_salary'  => $net,
                        'status'      => $isPaid ? 'paid' : 'draft',
                        'created_by'  => $adminId,
                    ]
                );
            }
        }

        // ===== الموردون =====
        $suppliers = [
            ['name' => 'شركة النور للكهرباء والمياه',    'phone' => '0112345678', 'category' => 'كهرباء ومياه',        'notes' => 'مزود خدمة الكهرباء والمياه الرئيسي'],
            ['name' => 'مؤسسة الأمل للنظافة',            'phone' => '0507654321', 'category' => 'نظافة ومستلزمات',     'notes' => 'توريد مواد التنظيف واللوازم الصحية'],
            ['name' => 'شركة الرافدين للصيانة',          'phone' => '0561234567', 'category' => 'صيانة وإصلاح',       'notes' => 'صيانة التكييف والسباكة والكهرباء'],
            ['name' => 'مطعم البيت العربي',               'phone' => '0124567890', 'category' => 'طعام وشراب',          'notes' => 'توريد وجبات للموظفين وبوفيه الضيافة'],
            ['name' => 'مستودع المنار للتجهيزات',        'phone' => '0508901234', 'category' => 'تجهيزات ومفروشات',   'notes' => 'توريد أثاث وتجهيزات الغرف'],
            ['name' => 'شركة الأمان للحراسة',            'phone' => '0114567890', 'category' => 'حراسة وأمن',          'notes' => 'خدمات الأمن والحراسة الليلية'],
        ];

        $createdSuppliers = [];
        foreach ($suppliers as $data) {
            $sup = Supplier::firstOrCreate(['name' => $data['name']], array_merge($data, ['is_active' => true]));
            $createdSuppliers[] = $sup;
        }

        // ===== المصروفات (آخر 45 يوماً) =====
        $supplierMap = collect($createdSuppliers)->keyBy('name');

        $expenseTemplates = [
            // كهرباء ومياه — شهرية
            ['category' => 'electricity', 'amount' => 1850000, 'currency' => 'YER', 'description' => 'فاتورة الكهرباء والمياه — الشهر الماضي',      'supplier' => 'شركة النور للكهرباء والمياه', 'days_ago' => 32],
            ['category' => 'electricity', 'amount' => 1920000, 'currency' => 'YER', 'description' => 'فاتورة الكهرباء والمياه — الشهر الحالي',       'supplier' => 'شركة النور للكهرباء والمياه', 'days_ago' => 2],

            // نظافة — أسبوعية
            ['category' => 'cleaning',    'amount' => 320000,  'currency' => 'YER', 'description' => 'مواد تنظيف وأدوات صحية — دفعة أسبوعية',        'supplier' => 'مؤسسة الأمل للنظافة',         'days_ago' => 28],
            ['category' => 'cleaning',    'amount' => 310000,  'currency' => 'YER', 'description' => 'مواد تنظيف وأدوات صحية — دفعة أسبوعية',        'supplier' => 'مؤسسة الأمل للنظافة',         'days_ago' => 21],
            ['category' => 'cleaning',    'amount' => 335000,  'currency' => 'YER', 'description' => 'مواد تنظيف وأدوات صحية — دفعة أسبوعية',        'supplier' => 'مؤسسة الأمل للنظافة',         'days_ago' => 14],
            ['category' => 'cleaning',    'amount' => 290000,  'currency' => 'YER', 'description' => 'مواد تنظيف وأدوات صحية — دفعة أسبوعية',        'supplier' => 'مؤسسة الأمل للنظافة',         'days_ago' => 7],

            // صيانة — متفرقة
            ['category' => 'maintenance', 'amount' => 750000,  'currency' => 'YER', 'description' => 'إصلاح تكييف غرف الطابق الثاني',                 'supplier' => 'شركة الرافدين للصيانة',       'days_ago' => 35],
            ['category' => 'maintenance', 'amount' => 420000,  'currency' => 'YER', 'description' => 'تصليح تسرب مياه في الحمام 305',                 'supplier' => 'شركة الرافدين للصيانة',       'days_ago' => 18],
            ['category' => 'maintenance', 'amount' => 210000,  'currency' => 'YER', 'description' => 'استبدال مفاتيح كهربائية وإضاءة',                'supplier' => 'شركة الرافدين للصيانة',       'days_ago' => 5],
            ['category' => 'maintenance', 'amount' => 1200000, 'currency' => 'YER', 'description' => 'صيانة دورية للمصعد',                             'supplier' => 'شركة الرافدين للصيانة',       'days_ago' => 40],

            // طعام — شبه يومي
            ['category' => 'food',        'amount' => 180000,  'currency' => 'YER', 'description' => 'وجبات الموظفين — الأسبوع الماضي',               'supplier' => 'مطعم البيت العربي',            'days_ago' => 8],
            ['category' => 'food',        'amount' => 165000,  'currency' => 'YER', 'description' => 'وجبات الموظفين — هذا الأسبوع',                  'supplier' => 'مطعم البيت العربي',            'days_ago' => 1],
            ['category' => 'food',        'amount' => 95000,   'currency' => 'YER', 'description' => 'ضيافة وقهوة لاجتماع الإدارة',                   'supplier' => 'مطعم البيت العربي',            'days_ago' => 12],

            // رواتب — الشهر الماضي
            ['category' => 'salary',      'amount' => 18000000,'currency' => 'YER', 'description' => 'صرف رواتب الموظفين — الشهر الماضي',             'supplier' => null,                           'days_ago' => 30],

            // تجهيزات — متفرقة
            ['category' => 'other',       'amount' => 2800000, 'currency' => 'YER', 'description' => 'شراء 4 مراتب جديدة للغرف',                      'supplier' => 'مستودع المنار للتجهيزات',     'days_ago' => 44],
            ['category' => 'other',       'amount' => 450000,  'currency' => 'YER', 'description' => 'لوازم مكتبية وأدوات الاستقبال',                 'supplier' => null,                           'days_ago' => 22],
            ['category' => 'other',       'amount' => 120000,  'currency' => 'YER', 'description' => 'تجديد بطاقات الغرف (Key Cards)',                 'supplier' => null,                           'days_ago' => 10],

            // حراسة — شهري
            ['category' => 'other',       'amount' => 900000,  'currency' => 'YER', 'description' => 'رسوم خدمة الأمن والحراسة — الشهر الماضي',       'supplier' => 'شركة الأمان للحراسة',         'days_ago' => 31],
            ['category' => 'other',       'amount' => 900000,  'currency' => 'YER', 'description' => 'رسوم خدمة الأمن والحراسة — الشهر الحالي',       'supplier' => 'شركة الأمان للحراسة',         'days_ago' => 1],

            // مصروفات بالريال السعودي
            ['category' => 'maintenance', 'amount' => 850,     'currency' => 'SAR', 'description' => 'قطع غيار تكييف — طلب خارجي',                    'supplier' => 'شركة الرافدين للصيانة',       'days_ago' => 15],
            ['category' => 'other',       'amount' => 1200,    'currency' => 'SAR', 'description' => 'شراء أجهزة كمبيوتر للاستقبال',                  'supplier' => null,                           'days_ago' => 38],
        ];

        foreach ($expenseTemplates as $t) {
            $supplierId = $t['supplier'] ? ($supplierMap[$t['supplier']]?->id ?? null) : null;
            Expense::create([
                'amount'       => $t['amount'],
                'currency'     => $t['currency'],
                'category'     => $t['category'],
                'supplier_id'  => $supplierId,
                'description'  => $t['description'],
                'expense_date' => now()->subDays($t['days_ago'])->toDateString(),
                'paid_by'      => $adminId,
                'shift_id'     => null,
            ]);
        }
    }
}
