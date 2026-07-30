<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فصل الخصم التلقائي (مسحوبات الموظف + خصم الغياب/الإجازة بدون راتب) عن
 * الخصومات اليدوية الأخرى، في عمودين مستقلّين يُعاد احتسابهما بالكامل في كل
 * مرة (استبدال لا جمع تراكمي) — بدل إضافتهما فوق عمود deductions الواحد، ما
 * كان سيُسبّب خصماً مضاعَفاً عند تفعيل الاحتساب التلقائي أكثر من مرة (مثال:
 * عند تعديل القسيمة). deductions يبقى للخصومات اليدوية الأخرى فقط.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->decimal('withdrawals_deduction', 10, 2)->default(0)->after('deductions');
            $table->decimal('attendance_deduction', 10, 2)->default(0)->after('withdrawals_deduction');
        });
    }
    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn(['withdrawals_deduction', 'attendance_deduction']);
        });
    }
};
