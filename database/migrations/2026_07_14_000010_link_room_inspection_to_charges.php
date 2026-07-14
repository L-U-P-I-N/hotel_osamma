<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ربط الرسم الإضافي والمصروف بالضرر المسجّل حتى يمكن تعديله أو حذفه لاحقاً
        Schema::table('extra_charges', function (Blueprint $table) {
            $table->foreignId('room_inspection_id')->nullable()->after('reservation_id')
                ->constrained('room_inspections')->nullOnDelete();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('room_inspection_id')->nullable()->after('id')
                ->constrained('room_inspections')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('extra_charges', function (Blueprint $table) {
            $table->dropForeign(['room_inspection_id']);
            $table->dropColumn('room_inspection_id');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['room_inspection_id']);
            $table->dropColumn('room_inspection_id');
        });
    }
};
