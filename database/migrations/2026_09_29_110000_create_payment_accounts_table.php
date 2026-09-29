<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * وسائل القبض والدفع: كل صفٍّ هنا وعاءُ مالٍ حقيقي يمكن عدّه أو كشفه —
 * درج وردية، خزنة، حساب بنكي بعينه، جهاز شبكة.
 *
 * كانت طريقة الدفع نصّاً (cash/bank_transfer/pos) بلا وجهة: كل دفعة تُرحَّل إلى
 * درج نقدية الوردية مهما كانت طريقتها، فيظهر التحويل البنكي نقداً في الدرج،
 * ويُطالَب الموظف عند الإقفال بنقدٍ لم يستلمه. والفندق له أكثر من حساب بنكي،
 * فلم يكن ممكناً معرفة رصيد كل حساب على حِدة.
 *
 * كل وسيلة مربوطة بحساب في شجرة USALI، فرصيدها يُقرأ من دفتر الأستاذ لا من
 * جمعٍ موازٍ قد ينحرف عنه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // shift_cash: درج الوردية | safe: خزنة | bank: حساب بنكي | pos: جهاز شبكة
            $table->string('type', 20);
            // الحساب في شجرة USALI الذي تُرحَّل إليه حركات هذه الوسيلة
            $table->string('account_code', 20);
            $table->string('bank_name')->nullable();
            $table->string('account_number', 60)->nullable();
            $table->string('iban', 60)->nullable();
            $table->string('currency', 3)->default('YER');
            // نسبة عمولة جهاز الشبكة — تُقتطع عند توريد المبلغ للبنك
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            // الوسيلة الافتراضية لكل طريقة دفع — تُختار تلقائياً في الشاشات
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->foreign('account_code')->references('code')->on('chart_of_accounts');
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_accounts');
    }
};
