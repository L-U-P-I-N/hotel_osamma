<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_settlement_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('currency', ['YER','SAR','USD'])->default('YER');
            $table->dateTime('withdrawal_date');
            $table->string('withdrawn_by_name');
            $table->string('handed_by_name');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cash_withdrawals'); }
};
