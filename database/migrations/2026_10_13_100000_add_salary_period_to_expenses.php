<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * شهر الراتب المصروف — مستقلّ عن تاريخ الصرف.
 *
 * كان خصم الموظف يُنسب إلى شهر تاريخ المصروف دائماً، ورواتب الشهر تُصرف بعد
 * انتهائه: فراتب سبتمبر المصروف في 12 أكتوبر كان يُخصم من راتب أكتوبر، فيظهر
 * الموظف مديناً بشهرٍ لم يُصرف له ودائناً بشهرٍ صُرف.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // فارغان في غير الرواتب، فيُنسب المصروف حينها إلى شهر تاريخه كما كان
            $table->unsignedTinyInteger('salary_month')->nullable()->after('employee_id');
            $table->unsignedSmallInteger('salary_year')->nullable()->after('salary_month');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['salary_month', 'salary_year']);
        });
    }
};
