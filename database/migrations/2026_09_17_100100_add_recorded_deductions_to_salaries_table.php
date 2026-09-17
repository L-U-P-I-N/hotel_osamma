<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * عمود مستقل لخصومات الشهر المسجَّلة (جدول salary_deductions)، على نمط خصمَي
 * المسحوبات والغياب: فصلها عن حقل "خصومات" اليدوي يمنع ازدواج الخصم عند إعادة
 * احتساب القسيمة، ويُبقي مصدر كل مبلغ واضحاً في قسيمة الراتب.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->decimal('recorded_deductions', 12, 2)->default(0)->after('attendance_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn('recorded_deductions');
        });
    }
};
