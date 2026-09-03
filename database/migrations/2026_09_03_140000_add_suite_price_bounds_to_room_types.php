<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            // نطاق سعر الجناح كاملاً (غرفتان) — مستقل عن نطاق القسم الواحد،
            // لأن الجناح قد يُباع بسعر عرض أقل من مجموع قسميه أو أعلى منه.
            // صفر يعني "غير مضبوط" فيُشتق النطاق من ضعف نطاق القسم كما كان.
            $table->decimal('suite_min_price', 10, 2)->default(0)->after('max_price');
            $table->decimal('suite_max_price', 10, 2)->default(0)->after('suite_min_price');
        });

        // الأنواع التي لها أقسام أجنحة تبدأ بضعف نطاق القسم — نفس السلوك السابق
        // بالضبط، حتى لا يتغيّر أي شيء قبل أن يضبطه المدير بنفسه.
        $suiteTypeIds = DB::table('rooms')
            ->whereIn('room_sub_type', ['suite_a', 'suite_b'])
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('room_type_id');

        foreach ($suiteTypeIds as $typeId) {
            $type = DB::table('room_types')->where('id', $typeId)->first();
            if (!$type) {
                continue;
            }

            $min = (float) $type->min_price > 0 ? (float) $type->min_price : (float) $type->base_price;
            $max = (float) $type->max_price >= $min ? (float) $type->max_price : $min;

            DB::table('room_types')->where('id', $typeId)->update([
                'suite_min_price' => round($min * 2, 2),
                'suite_max_price' => round($max * 2, 2),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['suite_min_price', 'suite_max_price']);
        });
    }
};
