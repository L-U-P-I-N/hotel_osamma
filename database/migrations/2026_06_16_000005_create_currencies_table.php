<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name');                            // ريال يمني
            $table->string('symbol', 10);                     // ر.ي
            $table->string('code', 10)->unique();             // YER
            $table->decimal('exchange_rate_to_yer', 12, 2)->default(1); // للمعلومية فقط
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
