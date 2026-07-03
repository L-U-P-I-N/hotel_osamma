<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * إلغاء الحجز يعتمد على الحذف الناعم (soft delete) الموجود أصلاً على
     * Reservation، وهذه الأعمدة تسجّل سبب الإلغاء ومن ألغاه ومتى — لتبقى
     * بيانات النزيل والمتأخرات محفوظة لأغراض المتابعة بدل أن تُحذف نهائياً.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('cancellation_reason', 500)->nullable()->after('notes');
            $table->foreignId('cancelled_by')->nullable()->after('cancellation_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['cancellation_reason', 'cancelled_at']);
        });
    }
};
