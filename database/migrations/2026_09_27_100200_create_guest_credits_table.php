<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مبالغ متبقية للنزلاء (أمانات دائنة): نزيل دفع ليلتين ثم غادر بعد ليلة واحدة
 * يبقى له فرق لم يُصرف له وقت الخروج. كان الخروج يتعطّل أو يضيع الفرق؛ هنا
 * يُرحَّل الفرق إلى رصيد باسم النزيل يُصرف له لاحقاً (أو يُسقطه بنفسه)، ويظهر
 * في صفحة مستقلة حتى لا يبقى التزامٌ على الفندق غير مرئي.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            // النزيل مكرَّر هنا عن عمد: الرصيد يُبحَث عنه باسم النزيل بعد أن
            // يكون حجزه قد أُرشِف أو حُذف منطقياً
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('YER');
            // open: مستحق للنزيل | settled: صُرف له | cancelled: تنازل عنه
            $table->string('status', 20)->default('open');
            $table->string('reason', 500);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // بيانات الصرف — تُملأ عند تسليم المبلغ للنزيل
            $table->timestamp('settled_at')->nullable();
            $table->foreignId('settled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('settlement_method', 30)->nullable();
            $table->string('settlement_notes', 500)->nullable();
            // مستلمة الصرف: صفّ استرجاع يُخرج المبلغ من صندوق الوردية
            $table->foreignId('refund_id')->nullable()->constrained('refunds')->nullOnDelete();

            $table->timestamps();

            // الاستعلام الأكثر تكراراً: الأرصدة القائمة الأحدث أولاً
            $table->index(['status', 'created_at']);
            $table->index('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_credits');
    }
};
