<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('floors', function (Blueprint $table) {
            $table->id();
            $table->integer('floor_number')->unique();
            $table->integer('door_count')->default(10);
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('floors');
    }
};
