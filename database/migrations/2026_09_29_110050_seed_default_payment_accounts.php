<?php

use App\Models\ChartOfAccount;
use App\Models\PaymentAccount;
use Database\Seeders\PaymentAccountsSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * الأوعية المالية الافتراضية للقواعد القائمة.
 *
 * على تركيب جديد لا تفعل شيئاً: شجرة الحسابات تُبذَر بعد الترحيلات، وكل وعاء
 * مرتبط بحساب فيها — فيتولّى PaymentAccountsSeeder البذر في موضعه الصحيح.
 * أما القاعدة العاملة فشجرتها مبذورة بالفعل، فتُنشأ أوعيتها هنا حتى لا تنتظر
 * تشغيل بذرة يدوية بعد التحديث.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!ChartOfAccount::exists()) {
            return;
        }

        (new PaymentAccountsSeeder())->run();
    }

    public function down(): void
    {
        PaymentAccount::whereIn('account_code', ['1111', '1120', '1131', '1140'])->delete();
    }
};
