<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('linked_room_id')->nullable()->after('room_id')
                  ->references('id')->on('rooms')->nullOnDelete();
            $table->enum('suite_booking_type', ['a_only','b_only','both'])->nullable()->after('linked_room_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['linked_room_id']);
            $table->dropColumn(['linked_room_id','suite_booking_type']);
        });
    }
};
