<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            // نطاق سعر الليلة المسموح للموظف — يحدده المدير من صفحة إعدادات التسعير
            $table->decimal('min_price', 10, 2)->default(0)->after('base_price');
            $table->decimal('max_price', 10, 2)->default(0)->after('min_price');
        });

        // النطاق الابتدائي يجب أن يستوعب كل الأسعار المستخدمة فعلاً اليوم،
        // وإلا صارت غرف قائمة غير قابلة للحجز فور تطبيق الترحيل.
        foreach (DB::table('room_types')->select('id', 'base_price')->get() as $type) {
            $base = (float) $type->base_price;

            $roomPrices = DB::table('rooms')
                ->where('room_type_id', $type->id)
                ->whereNull('deleted_at')
                ->get(['price_yer', 'price_sar', 'price_usd']);

            $candidates = [$base];
            foreach ($roomPrices as $room) {
                // أسعار العملات الأخرى ليست بالريال فلا تدخل في النطاق اليمني
                if ($room->price_yer !== null && (float) $room->price_yer > 0) {
                    $candidates[] = (float) $room->price_yer;
                }
            }

            $candidates = array_filter($candidates, fn($p) => $p > 0);
            if (empty($candidates)) {
                continue;
            }

            DB::table('room_types')->where('id', $type->id)->update([
                'min_price' => min($candidates),
                'max_price' => max($candidates),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['min_price', 'max_price']);
        });
    }
};
