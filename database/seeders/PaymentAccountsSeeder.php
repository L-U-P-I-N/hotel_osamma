<?php

namespace Database\Seeders;

use App\Models\PaymentAccount;
use Illuminate\Database\Seeder;

/**
 * الأوعية المالية الافتراضية: درج الوردية، الخزنة، حساب بنكي، جهاز شبكة.
 *
 * تُبذَر **بعد** شجرة الحسابات لأن كل وعاء مرتبط بحساب فيها. يضيف الفندق ما
 * يشاء بعدها من شاشة «الصناديق والبنوك» — حساباً بنكياً ثانياً مثلاً — ويُنشأ
 * له حساب في الشجرة تلقائياً.
 */
class PaymentAccountsSeeder extends Seeder
{
    public const DEFAULTS = [
        ['name' => 'درج نقدية الوردية',     'type' => 'shift_cash', 'account_code' => '1111', 'is_default' => true,  'sort_order' => 1],
        ['name' => 'الصندوق العام',          'type' => 'safe',       'account_code' => '1120', 'is_default' => false, 'sort_order' => 2],
        ['name' => 'الحساب البنكي الرئيسي',  'type' => 'bank',       'account_code' => '1131', 'is_default' => true,  'sort_order' => 3],
        ['name' => 'جهاز الشبكة (POS)',      'type' => 'pos',        'account_code' => '1140', 'is_default' => true,  'sort_order' => 4],
    ];

    public function run(): void
    {
        foreach (self::DEFAULTS as $row) {
            PaymentAccount::firstOrCreate(
                ['type' => $row['type'], 'account_code' => $row['account_code']],
                $row + ['currency' => 'YER', 'is_active' => true]
            );
        }
    }
}
