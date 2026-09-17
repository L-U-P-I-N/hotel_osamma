<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * خصومات الراتب: عقوبات وتعويضات تُسجَّل على الموظف وقت حدوثها بسببها وتاريخها
 * ومَن سجّلها — بدل رقمٍ مجرَّد يُكتب في قسيمة آخر الشهر بلا أثر ولا مسؤول.
 * خصومات الشهر تُجمع تلقائياً في قسيمة راتبه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            // سبب الخصم: قيمة نصّية لا enum — إضافة سبب جديد لاحقاً لا تحتاج
            // تعديل بنية الجدول (تغيير enum غير متّسق بين MySQL وSQLite).
            $table->string('reason', 40)->default('other');
            $table->text('description')->nullable();
            $table->date('deduction_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // الاستعلام الأكثر تكراراً: خصومات موظف في شهر معيّن (قسيمة الراتب)
            $table->index(['employee_id', 'deduction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_deductions');
    }
};
