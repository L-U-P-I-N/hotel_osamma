<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspected_by')->constrained('users');
            $table->boolean('has_damage')->default(false);
            $table->text('damage_description')->nullable();
            $table->decimal('compensation_amount', 10, 2)->default(0);
            $table->enum('compensation_status', ['none','pending','paid'])->default('none');
            $table->dateTime('inspection_date');
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('room_inspections'); }
};
