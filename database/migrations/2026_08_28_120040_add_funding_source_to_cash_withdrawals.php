<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * يميّز سحب المصروف من الوردية الشخصية عن سحبه من الصندوق العام (خزنة رئيسية
 * لا ترتبط بوردية) — يحدد الحساب المحاسبي (1110 أو 1120) الذي يُقيَّد له السحب.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('cash_withdrawals', function (Blueprint $table) {
            $table->enum('funding_source', ['shift', 'general_safe'])->default('shift')->after('employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('cash_withdrawals', function (Blueprint $table) {
            $table->dropColumn('funding_source');
        });
    }
};
