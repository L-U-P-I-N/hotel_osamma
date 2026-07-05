<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * إصلاح بيانات رجعي: حجوزات بتاريخ وصول مستقبلي كانت تجعل الغرفة "مشغولة"
     * فوراً عند الإنشاء (قبل هذا الإصلاح)، رغم أن النزيل لم يصل بعد فعلياً.
     * نُعيد أي غرفة إلى "متاحة" إن كان سبب إشغالها الوحيد حجزاً لم يبدأ بعد،
     * ولا يوجد لها أي حجز نشط آخر بدأ فعلياً (تاريخ وصول اليوم أو قبله).
     */
    public function up(): void
    {
        $occupiedRoomIds = DB::table('rooms')->where('status', 'occupied')->pluck('id');

        foreach ($occupiedRoomIds as $roomId) {
            // الغرفة قد تكون الغرفة الرئيسية للحجز أو غرفته المرتبطة (جناح A+B/شقة)
            $hasStarted = DB::table('reservations')
                ->where(function ($q) use ($roomId) {
                    $q->where('room_id', $roomId)->orWhere('linked_room_id', $roomId);
                })
                ->where('status', 'checked_in')
                ->whereNull('deleted_at')
                ->whereDate('check_in_date', '<=', now()->toDateString())
                ->exists();

            if (!$hasStarted) {
                DB::table('rooms')->where('id', $roomId)->update(['status' => 'available']);
            }
        }
    }

    public function down(): void
    {
        // لا رجوع — إعادة إشغال الغرف حسب حجوزات مستقبلية سلوك غير مرغوب أصلاً.
    }
};
