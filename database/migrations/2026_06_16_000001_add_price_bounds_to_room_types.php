<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->decimal('min_price', 10, 2)->default(0)->after('base_price');
            $table->decimal('max_price', 10, 2)->default(0)->after('min_price');
        });

        // الأنواع الموجودة تبدأ بنطاق مغلق على السعر الأساسي حتى يوسّعه المدير،
        // حتى لا يفتح الترحيل ثغرة تسعير غير محدودة على بيانات قائمة.
        DB::table('room_types')->update([
            'min_price' => DB::raw('base_price'),
            'max_price' => DB::raw('base_price'),
        ]);
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn(['min_price', 'max_price']);
        });
    }
};
