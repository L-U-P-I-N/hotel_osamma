<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;

class AccountsSeeder extends Seeder
{
    public function run(): void
    {
        // [code, name, type, normal_balance, parent_code]
        $accounts = [
            ['1000', 'الأصول', 'asset', 'debit', null],
            ['1100', 'النقدية', 'asset', 'debit', '1000'],
            ['1110', 'نقدية الورديات', 'asset', 'debit', '1100'],
            ['1120', 'الصندوق العام', 'asset', 'debit', '1100'],
            ['1200', 'ديون النزلاء (ذمم مدينة)', 'asset', 'debit', '1000'],
            ['1300', 'ديون المشتريات (بقالة)', 'asset', 'debit', '1000'],

            ['2000', 'الخصوم', 'liability', 'credit', null],
            ['2100', 'رواتب مستحقة', 'liability', 'credit', '2000'],
            ['2200', 'مصروفات مستحقة (تحويل/لاحق)', 'liability', 'credit', '2000'],

            ['3000', 'حقوق الملكية', 'equity', 'credit', null],
            ['3100', 'رأس المال / الأرباح المرحّلة', 'equity', 'credit', '3000'],

            ['4000', 'الإيرادات', 'revenue', 'credit', null],
            ['4100', 'إيرادات الغرف', 'revenue', 'credit', '4000'],
            ['4200', 'إيرادات تعويض أضرار', 'revenue', 'credit', '4000'],

            ['5000', 'المصروفات', 'expense', 'debit', null],
            ['5100', 'صيانة', 'expense', 'debit', '5000'],
            ['5200', 'كهرباء/مياه', 'expense', 'debit', '5000'],
            ['5300', 'رواتب', 'expense', 'debit', '5000'],
            ['5400', 'نظافة', 'expense', 'debit', '5000'],
            ['5500', 'طعام وشراب', 'expense', 'debit', '5000'],
            ['5600', 'أخرى', 'expense', 'debit', '5000'],
        ];

        $idsByCode = [];

        foreach ($accounts as [$code, $name, $type, $normalBalance, $parentCode]) {
            $account = Account::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'type' => $type,
                    'normal_balance' => $normalBalance,
                    'parent_id' => $parentCode ? ($idsByCode[$parentCode] ?? null) : null,
                    'is_active' => true,
                ]
            );

            $idsByCode[$code] = $account->id;
        }
    }
}
