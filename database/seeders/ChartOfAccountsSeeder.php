<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * شجرة حسابات فندقية كاملة وفق معيار USALI.
 * Full USALI-compliant hotel chart of accounts.
 *
 * البذرة عديمة الأثر التراكمي (idempotent): updateOrCreate على code، فإعادة
 * تشغيلها تُحدّث الأسماء والخصائص دون تكرار صف واحد ودون لمس القيود المرحّلة.
 *
 * ترتيب الإدراج من الجذر إلى الورقة، فمفتاح parent_code الأجنبي يجد أباه.
 */
class ChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        // [code, parent_code, name_en, name_ar, type, subtype, department, is_posting, level]
        $accounts = [

            // ═══════════════════ 1000 — ASSETS / الأصول ═══════════════════
            ['1000', null,   'ASSETS',                          'الأصول',                              'asset', 'current',      null, false, 1],

            // 1100 Cash & Bank / النقدية والبنوك
            ['1100', '1000', 'Cash & Bank',                     'النقدية والبنوك',                     'asset', 'current',      null, false, 2],
            ['1110', '1100', 'Front Office Cash Float',         'عهدة صندوق الاستقبال',                'asset', 'current',      null, true,  3],
            ['1111', '1110', 'Shift Cash Drawer',               'درج نقدية الوردية',                   'asset', 'current',      null, true,  4],
            ['1112', '1110', 'Petty Cash',                      'المصروفات النثرية',                   'asset', 'current',      null, true,  4],
            ['1120', '1100', 'General Safe',                    'الصندوق العام',                       'asset', 'current',      null, true,  3],
            ['1130', '1100', 'Bank — Current Account',          'البنك — الحساب الجاري',               'asset', 'current',      null, true,  3],
            ['1131', '1130', 'Bank — Local Currency',           'البنك — بالعملة المحلية',             'asset', 'current',      null, true,  4],
            ['1132', '1130', 'Bank — Foreign Currency',         'البنك — بالعملة الأجنبية',            'asset', 'current',      null, true,  4],
            ['1140', '1100', 'Card Settlement Receivable',      'مستحقات شبكات الدفع',                 'asset', 'current',      null, true,  3],
            ['1150', '1100', 'Cash in Transit',                 'نقدية تحت التحصيل',                   'asset', 'current',      null, true,  3],

            // 1200 Accounts Receivable / الذمم المدينة
            ['1200', '1000', 'Accounts Receivable',             'الذمم المدينة',                       'asset', 'current',      null, false, 2],
            ['1210', '1200', 'Guest Ledger (In-House)',         'ذمم النزلاء المقيمين',                'asset', 'current',      'rooms', true, 3],
            ['1220', '1200', 'City Ledger (Checked Out)',       'ذمم النزلاء المغادرين',               'asset', 'current',      'rooms', true, 3],
            ['1230', '1200', 'Travel Agency Receivable',        'ذمم وكالات السفر',                    'asset', 'current',      'sales', true, 3],
            ['1231', '1230', 'OTA Receivable',                  'ذمم منصات الحجز الإلكتروني',          'asset', 'current',      'sales', true, 4],
            ['1240', '1200', 'Corporate Receivable',            'ذمم الشركات والعقود',                 'asset', 'current',      'sales', true, 3],
            ['1250', '1200', 'Employee Advances',               'سلف الموظفين',                        'asset', 'current',      'admin', true, 3],
            ['1260', '1200', 'Other Receivables',               'ذمم مدينة أخرى',                      'asset', 'current',      null, true,  3],
            ['1290', '1200', 'Allowance for Doubtful Accounts', 'مخصص الديون المشكوك في تحصيلها',      'asset', 'contra_asset', null, true,  3],

            // 1300 Inventory / المخزون
            ['1300', '1000', 'Inventory',                       'المخزون',                             'asset', 'current',      null, false, 2],
            ['1310', '1300', 'Food Inventory',                  'مخزون الأطعمة',                       'asset', 'current',      'fnb', true, 3],
            ['1320', '1300', 'Beverage Inventory',              'مخزون المشروبات',                     'asset', 'current',      'fnb', true, 3],
            ['1330', '1300', 'Minibar Inventory',               'مخزون الميني بار',                    'asset', 'current',      'fnb', true, 3],
            ['1340', '1300', 'Housekeeping Supplies',           'مستلزمات التدبير الفندقي',            'asset', 'current',      'rooms', true, 3],
            ['1341', '1340', 'Guest Amenities Stock',           'مخزون مستلزمات النزلاء',              'asset', 'current',      'rooms', true, 4],
            ['1342', '1340', 'Linen & Terry Stock',             'مخزون المفروشات والمناشف',            'asset', 'current',      'rooms', true, 4],
            ['1350', '1300', 'Engineering Spare Parts',         'قطع غيار الصيانة',                    'asset', 'current',      'maintenance', true, 3],
            ['1360', '1300', 'Stationery & Printing Stock',     'مخزون القرطاسية والمطبوعات',          'asset', 'current',      'admin', true, 3],

            // 1400 Prepaid Expenses / المصروفات المدفوعة مقدماً
            ['1400', '1000', 'Prepaid Expenses',                'المصروفات المدفوعة مقدماً',           'asset', 'current',      null, false, 2],
            ['1410', '1400', 'Prepaid Rent',                    'إيجار مدفوع مقدماً',                  'asset', 'current',      'admin', true, 3],
            ['1420', '1400', 'Prepaid Insurance',               'تأمين مدفوع مقدماً',                  'asset', 'current',      'admin', true, 3],
            ['1430', '1400', 'Prepaid Licenses & Subscriptions','تراخيص واشتراكات مدفوعة مقدماً',      'asset', 'current',      'admin', true, 3],
            ['1440', '1400', 'Prepaid Marketing',               'تسويق مدفوع مقدماً',                  'asset', 'current',      'sales', true, 3],
            ['1450', '1400', 'Supplier Advances',               'دفعات مقدمة للموردين',                'asset', 'current',      'admin', true, 3],

            // 1500 Fixed Assets / الأصول الثابتة
            ['1500', '1000', 'Fixed Assets',                    'الأصول الثابتة',                      'asset', 'fixed',        null, false, 2],
            ['1510', '1500', 'Land',                            'الأراضي',                             'asset', 'fixed',        null, true,  3],
            ['1520', '1500', 'Buildings',                       'المباني',                             'asset', 'fixed',        null, true,  3],
            ['1530', '1500', 'Furniture, Fixtures & Equipment', 'الأثاث والتجهيزات والمعدات',          'asset', 'fixed',        null, true,  3],
            ['1531', '1530', 'Guest Room FF&E',                 'أثاث وتجهيزات الغرف',                 'asset', 'fixed',        'rooms', true, 4],
            ['1532', '1530', 'Kitchen & Restaurant Equipment',  'معدات المطبخ والمطعم',                'asset', 'fixed',        'fnb', true, 4],
            ['1540', '1500', 'Vehicles',                        'المركبات',                            'asset', 'fixed',        null, true,  3],
            ['1550', '1500', 'IT & Computer Equipment',         'أجهزة الحاسب وتقنية المعلومات',       'asset', 'fixed',        'admin', true, 3],
            ['1560', '1500', 'Operating Equipment (China/Glass/Silver)', 'معدات التشغيل (أوانٍ وزجاجيات)', 'asset', 'fixed',  'fnb', true, 3],
            ['1570', '1500', 'Intangible Assets — Software',    'الأصول غير الملموسة — البرمجيات',     'asset', 'intangible',   'admin', true, 3],

            // 1600 Accumulated Depreciation / مجمّع الإهلاك (حسابات مقابلة — رصيد دائن)
            ['1600', '1000', 'Accumulated Depreciation',        'مجمّع الإهلاك',                       'asset', 'contra_asset', null, false, 2],
            ['1620', '1600', 'Accum. Depreciation — Buildings', 'مجمّع إهلاك المباني',                 'asset', 'contra_asset', null, true,  3],
            ['1630', '1600', 'Accum. Depreciation — FF&E',      'مجمّع إهلاك الأثاث والتجهيزات',       'asset', 'contra_asset', null, true,  3],
            ['1640', '1600', 'Accum. Depreciation — Vehicles',  'مجمّع إهلاك المركبات',                'asset', 'contra_asset', null, true,  3],
            ['1650', '1600', 'Accum. Depreciation — IT',        'مجمّع إهلاك أجهزة الحاسب',            'asset', 'contra_asset', null, true,  3],
            ['1670', '1600', 'Accum. Amortization — Software',  'مجمّع إطفاء البرمجيات',               'asset', 'contra_asset', null, true,  3],

            // ═══════════════════ 2000 — LIABILITIES / الخصوم ═══════════════════
            ['2000', null,   'LIABILITIES',                     'الخصوم',                              'liability', 'current_liability', null, false, 1],

            // 2100 Accounts Payable / الذمم الدائنة
            ['2100', '2000', 'Accounts Payable',                'الذمم الدائنة',                       'liability', 'current_liability', null, false, 2],
            ['2110', '2100', 'Trade Suppliers Payable',         'ذمم الموردين',                        'liability', 'current_liability', null, true, 3],
            ['2111', '2110', 'Food & Beverage Suppliers',       'موردو الأطعمة والمشروبات',            'liability', 'current_liability', 'fnb', true, 4],
            ['2112', '2110', 'Housekeeping Suppliers',          'موردو مستلزمات التدبير',              'liability', 'current_liability', 'rooms', true, 4],
            ['2113', '2110', 'Maintenance Suppliers',           'موردو قطع الصيانة',                   'liability', 'current_liability', 'maintenance', true, 4],
            ['2120', '2100', 'OTA Commission Payable',          'عمولات منصات الحجز المستحقة',         'liability', 'current_liability', 'sales', true, 3],
            ['2130', '2100', 'Travel Agency Commission Payable','عمولات وكالات السفر المستحقة',        'liability', 'current_liability', 'sales', true, 3],
            ['2140', '2100', 'Utilities Payable',               'فواتير المرافق المستحقة',             'liability', 'current_liability', 'utilities', true, 3],
            ['2150', '2100', 'Other Payables',                  'ذمم دائنة أخرى',                      'liability', 'current_liability', null, true, 3],

            // 2200 Guest Deposits / أمانات النزلاء
            ['2200', '2000', 'Guest Deposits & Advances',       'أمانات النزلاء والدفعات المقدمة',     'liability', 'current_liability', null, false, 2],
            ['2210', '2200', 'Advance Deposits — Reservations', 'عربون الحجوزات المقدمة',              'liability', 'current_liability', 'rooms', true, 3],
            ['2220', '2200', 'Guest Key & Damage Deposits',     'تأمينات المفاتيح والأضرار',           'liability', 'current_liability', 'rooms', true, 3],
            ['2230', '2200', 'Unclaimed Guest Credit Balances', 'أرصدة نزلاء دائنة غير مطالَب بها',    'liability', 'current_liability', 'rooms', true, 3],
            ['2240', '2200', 'Gift Vouchers Outstanding',       'قسائم هدايا غير مستخدمة',             'liability', 'current_liability', 'sales', true, 3],

            // 2300 Tax Payable / الضرائب المستحقة
            ['2300', '2000', 'Tax Payable',                     'الضرائب المستحقة',                    'liability', 'current_liability', null, false, 2],
            ['2310', '2300', 'VAT Output Payable',              'ضريبة القيمة المضافة — المخرجات',     'liability', 'current_liability', null, true, 3],
            ['2320', '2300', 'VAT Input Recoverable',           'ضريبة القيمة المضافة — المدخلات',     'liability', 'current_liability', null, true, 3],
            ['2330', '2300', 'Municipality / Tourism Levy',     'رسوم البلدية والسياحة',               'liability', 'current_liability', null, true, 3],
            ['2340', '2300', 'Withholding Tax Payable',         'ضريبة الاستقطاع المستحقة',            'liability', 'current_liability', null, true, 3],

            // 2400 Accrued Payroll / الرواتب المستحقة
            ['2400', '2000', 'Accrued Payroll & Benefits',      'الرواتب والمزايا المستحقة',           'liability', 'current_liability', 'admin', false, 2],
            ['2410', '2400', 'Accrued Salaries & Wages',        'رواتب وأجور مستحقة',                  'liability', 'current_liability', 'admin', true, 3],
            ['2420', '2400', 'Accrued Staff Meal Allowance',    'صرفية الطعام والشراب المستحقة',       'liability', 'current_liability', 'admin', true, 3],
            ['2430', '2400', 'Accrued End of Service Benefits', 'مكافأة نهاية الخدمة المستحقة',        'liability', 'current_liability', 'admin', true, 3],
            ['2440', '2400', 'Accrued Leave Provision',         'مخصص الإجازات المستحقة',              'liability', 'current_liability', 'admin', true, 3],
            ['2450', '2400', 'Social Insurance Payable',        'التأمينات الاجتماعية المستحقة',       'liability', 'current_liability', 'admin', true, 3],

            // 2500 Loans / القروض
            ['2500', '2000', 'Loans & Borrowings',              'القروض والتسهيلات',                   'liability', 'long_term_liability', null, false, 2],
            ['2510', '2500', 'Short-Term Loans',                'قروض قصيرة الأجل',                    'liability', 'current_liability',   null, true, 3],
            ['2520', '2500', 'Bank Overdraft',                  'السحب على المكشوف',                   'liability', 'current_liability',   null, true, 3],
            ['2530', '2500', 'Long-Term Loans',                 'قروض طويلة الأجل',                    'liability', 'long_term_liability', null, true, 3],
            ['2540', '2500', 'Current Portion of Long-Term Debt','الجزء المتداول من القروض طويلة الأجل','liability','current_liability',   null, true, 3],
            ['2550', '2500', 'Lease Liabilities',               'التزامات عقود الإيجار',               'liability', 'long_term_liability', null, true, 3],

            // ═══════════════════ 3000 — EQUITY / حقوق الملكية ═══════════════════
            ['3000', null,   'EQUITY',                          'حقوق الملكية',                        'equity', 'capital',  null, false, 1],
            ['3100', '3000', "Owner's Capital",                 'رأس مال المالك',                      'equity', 'capital',  null, false, 2],
            ['3110', '3100', 'Paid-in Capital',                 'رأس المال المدفوع',                   'equity', 'capital',  null, true,  3],
            ['3120', '3100', 'Additional Capital Contributions','إضافات رأس المال',                    'equity', 'capital',  null, true,  3],
            ['3130', '3100', 'Owner Drawings',                  'مسحوبات المالك',                      'equity', 'capital',  null, true,  3],
            ['3200', '3000', 'Retained Earnings',               'الأرباح المرحّلة',                    'equity', 'retained', null, false, 2],
            ['3210', '3200', 'Retained Earnings — Prior Years', 'الأرباح المرحّلة — سنوات سابقة',      'equity', 'retained', null, true,  3],
            ['3220', '3200', 'Current Year Profit / (Loss)',    'أرباح (خسائر) العام الحالي',          'equity', 'retained', null, true,  3],
            ['3230', '3200', 'Statutory Reserve',               'الاحتياطي النظامي',                   'equity', 'retained', null, true,  3],
            ['3240', '3200', 'Prior Period Adjustments',        'تسويات سنوات سابقة',                  'equity', 'retained', null, true,  3],

            // ═══════════════════ 4000 — REVENUE / الإيرادات ═══════════════════
            ['4000', null,   'REVENUE',                         'الإيرادات',                           'revenue', 'operating', null, false, 1],

            // 4100 Rooms / الغرف
            ['4100', '4000', 'Rooms Revenue',                   'إيرادات الغرف',                       'revenue', 'operating', 'rooms', false, 2],
            ['4110', '4100', 'Rack Rate Revenue',               'إيراد السعر المعلن',                  'revenue', 'operating', 'rooms', true, 3],
            ['4120', '4100', 'Corporate Contract Revenue',      'إيراد عقود الشركات',                  'revenue', 'operating', 'rooms', true, 3],
            ['4130', '4100', 'OTA / Online Channel Revenue',    'إيراد منصات الحجز الإلكتروني',        'revenue', 'operating', 'rooms', true, 3],
            ['4140', '4100', 'Walk-in Revenue',                 'إيراد الحجز المباشر',                 'revenue', 'operating', 'rooms', true, 3],
            ['4150', '4100', 'Travel Agency Revenue',           'إيراد وكالات السفر',                  'revenue', 'operating', 'rooms', true, 3],
            ['4160', '4100', 'Suite & Apartment Revenue',       'إيراد الأجنحة والشقق',                'revenue', 'operating', 'rooms', true, 3],
            ['4170', '4100', 'Extended Stay Revenue',           'إيراد الإقامات الممتدة',              'revenue', 'operating', 'rooms', true, 3],
            ['4180', '4100', 'No-Show & Cancellation Fees',     'رسوم عدم الحضور والإلغاء',            'revenue', 'operating', 'rooms', true, 3],
            ['4190', '4100', 'Rooms Allowances & Rebates',      'خصومات ومسموحات الغرف',               'revenue', 'contra_revenue', 'rooms', true, 3],

            // 4200 F&B / الأطعمة والمشروبات
            ['4200', '4000', 'Food & Beverage Revenue',         'إيرادات الأطعمة والمشروبات',          'revenue', 'operating', 'fnb', false, 2],
            ['4210', '4200', 'Restaurant Food Revenue',         'إيراد طعام المطعم',                   'revenue', 'operating', 'fnb', true, 3],
            ['4220', '4200', 'Restaurant Beverage Revenue',     'إيراد مشروبات المطعم',                'revenue', 'operating', 'fnb', true, 3],
            ['4230', '4200', 'Room Service Revenue',            'إيراد خدمة الغرف',                    'revenue', 'operating', 'fnb', true, 3],
            ['4240', '4200', 'Minibar Revenue',                 'إيراد الميني بار',                    'revenue', 'operating', 'fnb', true, 3],
            ['4250', '4200', 'Banquet & Events Revenue',        'إيراد المناسبات والولائم',            'revenue', 'operating', 'fnb', true, 3],
            ['4260', '4200', 'Coffee Shop Revenue',             'إيراد المقهى',                        'revenue', 'operating', 'fnb', true, 3],
            ['4270', '4200', 'Service Charge — F&B',            'رسوم الخدمة — الأطعمة والمشروبات',    'revenue', 'operating', 'fnb', true, 3],
            ['4290', '4200', 'F&B Allowances & Rebates',        'خصومات ومسموحات الأطعمة والمشروبات',  'revenue', 'contra_revenue', 'fnb', true, 3],

            // 4300 Spa & Recreation / المنتجع والترفيه
            ['4300', '4000', 'Spa & Recreation Revenue',        'إيرادات المنتجع والترفيه',            'revenue', 'other_operated', 'spa', false, 2],
            ['4310', '4300', 'Spa Treatments Revenue',          'إيراد جلسات المنتجع',                 'revenue', 'other_operated', 'spa', true, 3],
            ['4320', '4300', 'Health Club Membership',          'اشتراكات النادي الصحي',               'revenue', 'other_operated', 'spa', true, 3],
            ['4330', '4300', 'Swimming Pool Revenue',           'إيراد المسبح',                        'revenue', 'other_operated', 'spa', true, 3],
            ['4340', '4300', 'Spa Retail Products',             'مبيعات منتجات المنتجع',               'revenue', 'other_operated', 'spa', true, 3],

            // 4400 Laundry / المغسلة
            ['4400', '4000', 'Laundry Revenue',                 'إيرادات المغسلة',                     'revenue', 'other_operated', 'laundry', false, 2],
            ['4410', '4400', 'Guest Laundry Revenue',           'إيراد غسيل النزلاء',                  'revenue', 'other_operated', 'laundry', true, 3],
            ['4420', '4400', 'Dry Cleaning Revenue',            'إيراد التنظيف الجاف',                 'revenue', 'other_operated', 'laundry', true, 3],
            ['4430', '4400', 'Pressing & Ironing Revenue',      'إيراد الكي',                          'revenue', 'other_operated', 'laundry', true, 3],

            // 4500 Parking / المواقف
            ['4500', '4000', 'Parking Revenue',                 'إيرادات المواقف',                     'revenue', 'other_operated', 'parking', false, 2],
            ['4510', '4500', 'Guest Parking Revenue',           'إيراد مواقف النزلاء',                 'revenue', 'other_operated', 'parking', true, 3],
            ['4520', '4500', 'Valet Parking Revenue',           'إيراد خدمة صف السيارات',              'revenue', 'other_operated', 'parking', true, 3],

            // 4600 Other Operated Departments / أقسام تشغيلية أخرى
            ['4600', '4000', 'Other Operated Departments',      'أقسام تشغيلية أخرى',                  'revenue', 'other_operated', null, false, 2],
            ['4610', '4600', 'Telephone & Internet Revenue',    'إيراد الهاتف والإنترنت',              'revenue', 'other_operated', null, true, 3],
            ['4620', '4600', 'Business Center Revenue',         'إيراد مركز الأعمال',                  'revenue', 'other_operated', 'admin', true, 3],
            ['4630', '4600', 'Meeting Room Rental',             'إيجار قاعات الاجتماعات',              'revenue', 'other_operated', null, true, 3],
            ['4640', '4600', 'Airport Transfer Revenue',        'إيراد التوصيل من وإلى المطار',        'revenue', 'other_operated', null, true, 3],
            ['4650', '4600', 'Gift Shop Revenue',               'إيراد متجر الهدايا',                  'revenue', 'other_operated', null, true, 3],
            ['4660', '4600', 'Damage Compensation Revenue',     'إيراد تعويض الأضرار',                 'revenue', 'other_operated', 'rooms', true, 3],
            ['4700', '4000', 'Other Income',                    'إيرادات أخرى',                        'revenue', 'other_operated', null, false, 2],
            ['4710', '4700', 'Foreign Exchange Gain',           'أرباح فروق العملة',                   'revenue', 'other_operated', null, true, 3],
            ['4720', '4700', 'Interest Income',                 'إيرادات الفوائد',                     'revenue', 'other_operated', null, true, 3],
            ['4730', '4700', 'Miscellaneous Income',            'إيرادات متنوعة',                      'revenue', 'other_operated', null, true, 3],

            // ═══════════════════ 5000 — DEPARTMENTAL EXPENSES / المصروفات التشغيلية ═══════════════════
            ['5000', null,   'DEPARTMENTAL EXPENSES',           'المصروفات التشغيلية للأقسام',         'expense', 'operating', null, false, 1],

            // 5100 Rooms Department / قسم الغرف
            ['5100', '5000', 'Rooms Department Expenses',       'مصروفات قسم الغرف',                   'expense', 'operating', 'rooms', false, 2],
            ['5110', '5100', 'Rooms Payroll & Wages',           'رواتب وأجور قسم الغرف',               'expense', 'payroll',   'rooms', true, 3],
            ['5111', '5110', 'Rooms Staff Benefits',            'مزايا موظفي قسم الغرف',               'expense', 'payroll',   'rooms', true, 4],
            ['5112', '5110', 'Rooms Staff Meal Allowance',      'صرفية طعام موظفي الغرف',              'expense', 'payroll',   'rooms', true, 4],
            ['5120', '5100', 'Guest Amenities & Supplies',      'مستلزمات ومرافق النزلاء',             'expense', 'operating', 'rooms', true, 3],
            ['5130', '5100', 'Linen, Terry & Uniforms',         'المفروشات والمناشف والزي',            'expense', 'operating', 'rooms', true, 3],
            ['5140', '5100', 'Cleaning Supplies',               'مواد التنظيف',                        'expense', 'operating', 'rooms', true, 3],
            ['5150', '5100', 'Contract Laundry & Dry Cleaning', 'غسيل بعقود خارجية',                   'expense', 'operating', 'rooms', true, 3],
            ['5160', '5100', 'Reservation & Booking Fees',      'رسوم الحجز والقنوات',                 'expense', 'operating', 'rooms', true, 3],
            ['5170', '5100', 'Guest Transportation',            'نقل النزلاء',                         'expense', 'operating', 'rooms', true, 3],
            ['5180', '5100', 'Rooms Other Expenses',            'مصروفات أخرى لقسم الغرف',             'expense', 'operating', 'rooms', true, 3],

            // 5200 F&B Department / قسم الأطعمة والمشروبات
            ['5200', '5000', 'F&B Department Expenses',         'مصروفات قسم الأطعمة والمشروبات',      'expense', 'operating', 'fnb', false, 2],
            ['5210', '5200', 'Cost of Food Sold',               'تكلفة الأطعمة المباعة',               'expense', 'cogs',      'fnb', true, 3],
            ['5220', '5200', 'Cost of Beverage Sold',           'تكلفة المشروبات المباعة',             'expense', 'cogs',      'fnb', true, 3],
            ['5230', '5200', 'Cost of Minibar Sold',            'تكلفة مبيعات الميني بار',             'expense', 'cogs',      'fnb', true, 3],
            ['5240', '5200', 'F&B Payroll & Wages',             'رواتب وأجور قسم الأطعمة والمشروبات',  'expense', 'payroll',   'fnb', true, 3],
            ['5241', '5240', 'F&B Staff Benefits',              'مزايا موظفي الأطعمة والمشروبات',      'expense', 'payroll',   'fnb', true, 4],
            ['5250', '5200', 'Kitchen Fuel & Gas',              'وقود وغاز المطبخ',                    'expense', 'operating', 'fnb', true, 3],
            ['5260', '5200', 'Operating Supplies — F&B',        'مستلزمات تشغيل الأطعمة والمشروبات',   'expense', 'operating', 'fnb', true, 3],
            ['5270', '5200', 'Menu Printing & Decoration',      'طباعة القوائم والتزيين',              'expense', 'operating', 'fnb', true, 3],

            // 5300 Spa / المنتجع
            ['5300', '5000', 'Spa & Recreation Expenses',       'مصروفات المنتجع والترفيه',            'expense', 'operating', 'spa', false, 2],
            ['5310', '5300', 'Spa Payroll & Wages',             'رواتب وأجور المنتجع',                 'expense', 'payroll',   'spa', true, 3],
            ['5320', '5300', 'Spa Products & Consumables',      'مستهلكات ومنتجات المنتجع',            'expense', 'operating', 'spa', true, 3],
            ['5330', '5300', 'Pool Chemicals & Maintenance',    'كيماويات وصيانة المسبح',              'expense', 'operating', 'spa', true, 3],

            // 5400 Laundry / المغسلة
            ['5400', '5000', 'Laundry Department Expenses',     'مصروفات قسم المغسلة',                 'expense', 'operating', 'laundry', false, 2],
            ['5410', '5400', 'Laundry Payroll & Wages',         'رواتب وأجور المغسلة',                 'expense', 'payroll',   'laundry', true, 3],
            ['5420', '5400', 'Detergents & Laundry Supplies',   'منظفات ومستلزمات الغسيل',             'expense', 'operating', 'laundry', true, 3],
            ['5430', '5400', 'Laundry Equipment Rental',        'إيجار معدات المغسلة',                 'expense', 'operating', 'laundry', true, 3],

            // 5500 Parking / المواقف
            ['5500', '5000', 'Parking Department Expenses',     'مصروفات قسم المواقف',                 'expense', 'operating', 'parking', false, 2],
            ['5510', '5500', 'Parking Payroll & Wages',         'رواتب وأجور المواقف',                 'expense', 'payroll',   'parking', true, 3],
            ['5520', '5500', 'Parking Operating Supplies',      'مستلزمات تشغيل المواقف',              'expense', 'operating', 'parking', true, 3],

            // ═══════════════════ 6000 — UNDISTRIBUTED & FIXED / المصروفات غير الموزّعة والثابتة ═══════
            ['6000', null,   'UNDISTRIBUTED & FIXED CHARGES',   'المصروفات غير الموزّعة والأعباء الثابتة', 'expense', 'undistributed', null, false, 1],

            // 6100 Administrative & General / الإدارة والعموميات
            ['6100', '6000', 'Administrative & General',        'الإدارة والعموميات',                  'expense', 'undistributed', 'admin', false, 2],
            ['6110', '6100', 'Admin Payroll & Wages',           'رواتب وأجور الإدارة',                 'expense', 'payroll',       'admin', true, 3],
            ['6111', '6110', 'Admin Staff Benefits',            'مزايا موظفي الإدارة',                 'expense', 'payroll',       'admin', true, 4],
            ['6112', '6110', 'End of Service Benefits Expense', 'مصروف مكافأة نهاية الخدمة',           'expense', 'payroll',       'admin', true, 4],
            ['6120', '6100', 'Office Supplies & Printing',      'قرطاسية ومطبوعات',                    'expense', 'undistributed', 'admin', true, 3],
            ['6130', '6100', 'Professional & Legal Fees',       'أتعاب مهنية وقانونية',                'expense', 'undistributed', 'admin', true, 3],
            ['6140', '6100', 'Bank Charges & Card Commission',  'مصاريف بنكية وعمولات الشبكة',         'expense', 'undistributed', 'admin', true, 3],
            ['6150', '6100', 'IT & Software Subscriptions',     'اشتراكات تقنية وبرمجيات',             'expense', 'undistributed', 'admin', true, 3],
            ['6160', '6100', 'Bad Debt Expense',                'مصروف الديون المعدومة',               'expense', 'undistributed', 'admin', true, 3],
            ['6170', '6100', 'Foreign Exchange Loss',           'خسائر فروق العملة',                   'expense', 'undistributed', 'admin', true, 3],
            ['6180', '6100', 'Staff Training & Recruitment',    'تدريب وتوظيف الكوادر',                'expense', 'undistributed', 'admin', true, 3],
            ['6190', '6100', 'Other Administrative Expenses',   'مصروفات إدارية أخرى',                 'expense', 'undistributed', 'admin', true, 3],

            // 6200 Sales & Marketing / المبيعات والتسويق
            ['6200', '6000', 'Sales & Marketing',               'المبيعات والتسويق',                   'expense', 'undistributed', 'sales', false, 2],
            ['6210', '6200', 'Sales & Marketing Payroll',       'رواتب المبيعات والتسويق',             'expense', 'payroll',       'sales', true, 3],
            ['6220', '6200', 'Advertising & Promotion',         'الدعاية والترويج',                    'expense', 'undistributed', 'sales', true, 3],
            ['6230', '6200', 'Digital & Social Media Marketing','التسويق الرقمي ومنصات التواصل',       'expense', 'undistributed', 'sales', true, 3],
            ['6240', '6200', 'OTA Commission Expense',          'مصروف عمولات منصات الحجز',            'expense', 'undistributed', 'sales', true, 3],
            ['6250', '6200', 'Travel Agency Commission Expense','مصروف عمولات وكالات السفر',           'expense', 'undistributed', 'sales', true, 3],
            ['6260', '6200', 'Loyalty & Guest Relations',       'برامج الولاء وعلاقات النزلاء',        'expense', 'undistributed', 'sales', true, 3],

            // 6300 Maintenance & Engineering / الصيانة والهندسة
            ['6300', '6000', 'Property Operation & Maintenance','تشغيل وصيانة المنشأة',                'expense', 'undistributed', 'maintenance', false, 2],
            ['6310', '6300', 'Maintenance Payroll & Wages',     'رواتب وأجور الصيانة',                 'expense', 'payroll',       'maintenance', true, 3],
            ['6320', '6300', 'Building Repairs & Maintenance',  'صيانة وإصلاح المباني',                'expense', 'undistributed', 'maintenance', true, 3],
            ['6330', '6300', 'Equipment Repairs & Maintenance', 'صيانة وإصلاح المعدات',                'expense', 'undistributed', 'maintenance', true, 3],
            ['6340', '6300', 'Elevator & HVAC Contracts',       'عقود المصاعد والتكييف',               'expense', 'undistributed', 'maintenance', true, 3],
            ['6350', '6300', 'Grounds & Landscaping',           'الحدائق والمساحات الخارجية',          'expense', 'undistributed', 'maintenance', true, 3],
            ['6360', '6300', 'Waste Removal & Pest Control',    'النفايات ومكافحة الحشرات',            'expense', 'undistributed', 'maintenance', true, 3],

            // 6400 Utilities / المرافق
            ['6400', '6000', 'Utilities',                       'المرافق',                             'expense', 'undistributed', 'utilities', false, 2],
            ['6410', '6400', 'Electricity',                     'الكهرباء',                            'expense', 'undistributed', 'utilities', true, 3],
            ['6420', '6400', 'Water & Sewerage',                'المياه والصرف الصحي',                 'expense', 'undistributed', 'utilities', true, 3],
            ['6430', '6400', 'Gas & Fuel',                      'الغاز والوقود',                       'expense', 'undistributed', 'utilities', true, 3],
            ['6440', '6400', 'Generator Diesel',                'ديزل المولّدات',                      'expense', 'undistributed', 'utilities', true, 3],
            ['6450', '6400', 'Telephone & Internet',            'الهاتف والإنترنت',                    'expense', 'undistributed', 'utilities', true, 3],

            // 6500 Management Fees / أتعاب الإدارة
            ['6500', '6000', 'Management Fees',                 'أتعاب الإدارة',                       'expense', 'fixed_charges', 'admin', false, 2],
            ['6510', '6500', 'Base Management Fee',             'أتعاب الإدارة الأساسية',              'expense', 'fixed_charges', 'admin', true, 3],
            ['6520', '6500', 'Incentive Management Fee',        'أتعاب الإدارة التحفيزية',             'expense', 'fixed_charges', 'admin', true, 3],
            ['6530', '6500', 'Franchise & Brand Fees',          'رسوم الامتياز والعلامة التجارية',     'expense', 'fixed_charges', 'admin', true, 3],

            // 6600 Fixed Charges / الأعباء الثابتة
            ['6600', '6000', 'Fixed Charges',                   'الأعباء الثابتة',                     'expense', 'fixed_charges', null, false, 2],
            ['6610', '6600', 'Rent — Land & Building',          'إيجار الأرض والمبنى',                 'expense', 'fixed_charges', null, true, 3],
            ['6620', '6600', 'Equipment Rental',                'إيجار المعدات',                       'expense', 'fixed_charges', null, true, 3],
            ['6630', '6600', 'Property Insurance',              'تأمين الممتلكات',                     'expense', 'fixed_charges', null, true, 3],
            ['6640', '6600', 'Licenses & Government Fees',      'التراخيص والرسوم الحكومية',           'expense', 'fixed_charges', null, true, 3],
            ['6650', '6600', 'Depreciation Expense',            'مصروف الإهلاك',                       'expense', 'fixed_charges', null, true, 3],
            ['6651', '6650', 'Depreciation — Buildings',        'إهلاك المباني',                       'expense', 'fixed_charges', null, true, 4],
            ['6652', '6650', 'Depreciation — FF&E',             'إهلاك الأثاث والتجهيزات',             'expense', 'fixed_charges', null, true, 4],
            ['6660', '6600', 'Amortization Expense',            'مصروف الإطفاء',                       'expense', 'fixed_charges', null, true, 3],
            ['6670', '6600', 'Interest Expense',                'مصروف الفوائد',                       'expense', 'fixed_charges', null, true, 3],
            ['6680', '6600', 'Income Tax Expense',              'مصروف ضريبة الدخل',                   'expense', 'fixed_charges', null, true, 3],
        ];

        // الترتيب حسب المستوى ثم الكود يضمن وجود الأب قبل ابنه (قيد المفتاح الأجنبي)
        usort($accounts, static fn (array $a, array $b) => [$a[8], $a[0]] <=> [$b[8], $b[0]]);

        // قابلية الترحيل تُشتق من البنية لا من راية مكتوبة يدوياً: أي حساب له
        // ابن هو حساب تجميعي مهما كُتب أمامه. هذا يمنع خطأ "أب مفتوح للترحيل"
        // من التسلل عند إضافة حساب فرعي جديد لاحقاً.
        $hasChildren = [];
        foreach ($accounts as $row) {
            if ($row[1] !== null) {
                $hasChildren[$row[1]] = true;
            }
        }

        foreach ($accounts as $i => $row) {
            $accounts[$i][7] = !isset($hasChildren[$row[0]]) && $row[8] >= 3;
        }

        DB::transaction(static function () use ($accounts): void {
            foreach ($accounts as [$code, $parent, $nameEn, $nameAr, $type, $subtype, $dept, $isPosting, $level]) {
                ChartOfAccount::updateOrCreate(
                    ['code' => $code],
                    [
                        'parent_code'    => $parent,
                        'name_en'        => $nameEn,
                        'name_ar'        => $nameAr,
                        'type'           => $type,
                        'subtype'        => $subtype,
                        'department'     => $dept,
                        'is_posting'     => $isPosting,
                        // يُشتق في الموديل، ويُمرَّر هنا ليصمد لو استُدعي بـinsert
                        'normal_balance' => ChartOfAccount::normalBalanceFor($type, $subtype),
                        'is_active'      => true,
                        'level'          => $level,
                    ]
                );
            }
        });
    }
}
