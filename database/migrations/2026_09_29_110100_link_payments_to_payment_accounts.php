<?php

use App\Models\PaymentAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ربط كل مقبوض ومصروف بوعائه المالي.
 *
 * الدفعات القائمة تُنسب إلى الوسيلة الافتراضية لطريقتها: النقدي إلى درج الوردية،
 * والتحويل إلى الحساب البنكي الرئيسي، والشبكة إلى جهاز الشبكة. النسبة تقريبية
 * للتاريخ (لا سبيل لمعرفة أي حساب بنكي استُعمل قبل وجود الحقل) لكنها أدقّ بكثير
 * من الحال السابق حيث كان كل شيء نقداً في الدرج.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['payments', 'refunds'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('payment_account_id')->nullable()->after('method')
                  ->constrained('payment_accounts')->nullOnDelete();
            });
        }

        $defaults = [
            'cash'          => PaymentAccount::where('type', 'shift_cash')->orderBy('id')->first()?->id,
            'bank_transfer' => PaymentAccount::where('type', 'bank')->orderBy('id')->first()?->id,
            'pos'           => PaymentAccount::where('type', 'pos')->orderBy('id')->first()?->id,
        ];

        foreach (['payments', 'refunds'] as $table) {
            foreach ($defaults as $method => $accountId) {
                if ($accountId === null) {
                    continue;
                }
                DB::table($table)->where('method', $method)->whereNull('payment_account_id')
                    ->update(['payment_account_id' => $accountId]);
            }
        }
    }

    public function down(): void
    {
        foreach (['payments', 'refunds'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('payment_account_id');
            });
        }
    }
};
