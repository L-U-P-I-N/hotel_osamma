<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            // هل خُصم هذا الاسترجاع من "المدفوع" على الحجز؟ استرجاع الإلغاء لا
            // يُخصم عمداً (يبقى المدفوع سجلاً تاريخياً)، بينما استرجاع النزيل
            // العادي يُخصم. تسجيل ذلك صراحةً يجعل إعادة حساب المدفوع من مصدرها
            // ممكنة بدقة بدل التخمين.
            $table->boolean('affects_paid_amount')->default(true)->after('amount');
        });

        // الصفوف القائمة: استرجاعات الحجوزات الملغاة وحدها هي التي لم تُخصم
        DB::table('refunds')
            ->whereIn('reservation_id', function ($q) {
                $q->select('id')->from('reservations')->whereNotNull('cancelled_at');
            })
            ->update(['affects_paid_amount' => false]);
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropColumn('affects_paid_amount');
        });
    }
};
