<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seasonal_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->nullable()->constrained('room_types')->nullOnDelete();
            $table->string('name', 100);
            $table->date('from_date');
            $table->date('to_date');
            $table->enum('price_mode', ['fixed', 'multiplier'])->default('fixed');
            $table->decimal('price_yer', 12, 2)->nullable();
            $table->decimal('multiplier', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasonal_prices');
    }
};
