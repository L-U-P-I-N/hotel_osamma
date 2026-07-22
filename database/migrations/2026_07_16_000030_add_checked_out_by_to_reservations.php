<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * الموظف الذي نفّذ تسجيل خروج النزيل — يُعرَض في تقرير الحجوزات (عند فلتر
     * "المغادرون فقط") ليُعرَف من أنهى مغادرة كل نزيل. يبقى null للحجوزات التي
     * لم يُسجَّل خروجها أو التي غادرت قبل إضافة هذا الحقل.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('checked_out_by')->nullable()->after('actual_check_out')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['checked_out_by']);
            $table->dropColumn('checked_out_by');
        });
    }
};
