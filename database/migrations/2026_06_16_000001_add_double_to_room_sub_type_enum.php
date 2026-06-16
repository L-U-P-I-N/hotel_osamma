<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('room_sub_type', ['regular','double','suite_a','suite_b','apartment','hall'])
                  ->default('regular')->change();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->enum('room_sub_type', ['regular','suite_a','suite_b','apartment','hall'])
                  ->default('regular')->change();
        });
    }
};
