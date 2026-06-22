<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['room_type_id']);
            $table->foreignId('room_type_id')->nullable()->change();
            $table->foreign('room_type_id')->references('id')->on('room_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['room_type_id']);
            $table->foreignId('room_type_id')->nullable(false)->change();
            $table->foreign('room_type_id')->references('id')->on('room_types')->cascadeOnDelete();
        });
    }
};
