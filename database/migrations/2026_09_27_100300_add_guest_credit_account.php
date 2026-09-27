<?php

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;

/**
 * حساب الخصوم الذي تُقيَّد فيه المبالغ المتبقية للنزلاء. المبلغ يبقى نقداً في
 * الصندوق لكنه ليس إيراداً للفندق، فيُنقل من الإيراد (4100) إلى التزام على
 * الفندق (2300) حتى يُصرف للنزيل أو يتنازل عنه.
 */
return new class extends Migration
{
    public function up(): void
    {
        $liabilities = Account::where('code', '2000')->first();

        Account::firstOrCreate(
            ['code' => '2300'],
            [
                'name'           => 'مبالغ متبقية للنزلاء (أمانات دائنة)',
                'type'           => 'liability',
                'normal_balance' => 'credit',
                'parent_id'      => $liabilities?->id,
                'is_active'      => true,
            ]
        );
    }

    public function down(): void
    {
        Account::where('code', '2300')->delete();
    }
};
