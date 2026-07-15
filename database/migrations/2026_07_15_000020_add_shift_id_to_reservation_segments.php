<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * يربط كل فترة تجديد بالوردية التي كانت مفتوحة عند إنشائها، حتى نمنع تعديل
     * أو حذف تجديدٍ يعود لوردية أُقفلت بالفعل — فلا تتغيّر أرقام حجزٍ يخصّ عمل
     * وردية سابقة منتهية ومُصفّاة.
     */
    public function up(): void
    {
        Schema::table('reservation_segments', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('created_by')
                ->constrained('shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservation_segments', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
        });
    }
};
