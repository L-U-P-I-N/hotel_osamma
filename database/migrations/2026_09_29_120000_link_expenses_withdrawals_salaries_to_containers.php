<?php

use App\Models\PaymentAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ربط ما يخرج من المال بوعائه، كما رُبط ما يدخله.
 *
 * كان المصروف يُقيَّد خصماً من درج الوردية دائماً مهما كان مصدره، والسحبية
 * محصورة بين درجٍ وخزنة بلا خيار ثالث، والراتب مثبَّتاً على الصندوق العام. فلم
 * يكن ممكناً أن يُدفع مصروف بتحويل بنكي ويظهر خصماً من البنك.
 *
 * النسبة للحركات القائمة تتبع ما قُيِّد لها فعلاً وقت إنشائها، فلا يتغيّر رصيد
 * أي حساب بعد هذه الترحيلة.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['expenses', 'cash_withdrawals', 'salaries'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('payment_account_id')->nullable()
                  ->constrained('payment_accounts')->nullOnDelete();
            });
        }

        $shiftCash = PaymentAccount::where('type', PaymentAccount::TYPE_SHIFT_CASH)->orderBy('id')->first()?->id;
        $safe      = PaymentAccount::where('type', PaymentAccount::TYPE_SAFE)->orderBy('id')->first()?->id;
        $bank      = PaymentAccount::where('type', PaymentAccount::TYPE_BANK)->orderBy('id')->first()?->id;

        // المصروف النقدي كان يُقيَّد على درج الوردية (1111)، والتحويل/اللاحق على
        // ذمة دائنة بلا وعاء — فننسب النقدي وحده كي لا يتغيّر رصيد محسوب سلفاً.
        if ($shiftCash) {
            DB::table('expenses')->where('payment_method', 'cash')
                ->whereNull('payment_account_id')->update(['payment_account_id' => $shiftCash]);
        }

        // السحبية كانت تتبع funding_source: الوردية ⇒ الدرج، وغيرها ⇒ الخزنة
        if ($shiftCash) {
            DB::table('cash_withdrawals')->where('funding_source', '!=', 'general_safe')
                ->whereNull('payment_account_id')->update(['payment_account_id' => $shiftCash]);
        }
        if ($safe) {
            DB::table('cash_withdrawals')->where('funding_source', 'general_safe')
                ->whereNull('payment_account_id')->update(['payment_account_id' => $safe]);
        }

        // الراتب كان مثبَّتاً على الصندوق العام (1120)
        if ($safe) {
            DB::table('salaries')->where('status', 'paid')
                ->whereNull('payment_account_id')->update(['payment_account_id' => $safe]);
        }

        unset($bank);
    }

    public function down(): void
    {
        foreach (['expenses', 'cash_withdrawals', 'salaries'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropConstrainedForeignId('payment_account_id');
            });
        }
    }
};
