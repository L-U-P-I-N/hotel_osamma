<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('room_sub_type', ['regular','suite_a','suite_b','apartment','hall'])
                  ->default('regular')->after('room_number');
            $table->foreignId('linked_room_id')->nullable()->after('room_sub_type')
                  ->references('id')->on('rooms')->nullOnDelete();
            $table->boolean('is_always_linked')->default(false)->after('linked_room_id');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['linked_room_id']);
            $table->dropColumn(['room_sub_type','linked_room_id','is_always_linked']);
        });
    }
};
