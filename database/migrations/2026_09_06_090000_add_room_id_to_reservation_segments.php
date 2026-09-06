<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * كل فترة محاسبة تخصّ غرفة بعينها — كانت الفترات تسجّل السعر والليالي فقط
 * دون الغرفة، فإذا نُقل النزيل من غرفة إلى أخرى ضاعت معرفة أي الفترات
 * تخصّ الغرفة القديمة، ولم يكن ممكناً طباعة فاتورة لغرفة سابقة وحدها.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_segments', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('reservation_id')
                  ->constrained('rooms')->nullOnDelete();
        });

        // تعبئة أولية لكل الفترات القائمة بالغرفة الحالية للحجز — بحلقة PHP لا
        // UPDATE...JOIN لأن sqlite (المستخدَمة في الاختبارات) لا تدعمه.
        // تصحيح دقيق للحجوزات التي نُقلت فعلاً بين غرف يجريه أمر artisan منفصل
        // (segments:backfill-rooms) يعتمد على سجل المراجعة لمعرفة الغرفة
        // الفعلية في كل فترة تاريخياً.
        DB::table('reservations')->select('id', 'room_id')->orderBy('id')
            ->chunkById(500, function ($reservations) {
                foreach ($reservations as $reservation) {
                    if ($reservation->room_id !== null) {
                        DB::table('reservation_segments')
                            ->where('reservation_id', $reservation->id)
                            ->update(['room_id' => $reservation->room_id]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('reservation_segments', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropColumn('room_id');
        });
    }
};
