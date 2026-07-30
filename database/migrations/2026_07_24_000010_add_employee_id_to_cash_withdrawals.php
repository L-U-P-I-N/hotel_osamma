<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ربط السحب (الصرفية) بموظف صريح (اختياري) — يميّز سحباً هو فعلياً سلفة/مسحوب
 * شخصي لموظف عن سحب عابر باسم نصي حر (مورّد، صاحب الفندق...). فقط السحب
 * المربوط بموظف يدخل في احتساب خصم الرواتب التلقائي.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cash_withdrawals', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('handed_by_name')
                  ->constrained('employees')->nullOnDelete();
        });
    }
    public function down(): void
    {
        Schema::table('cash_withdrawals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
