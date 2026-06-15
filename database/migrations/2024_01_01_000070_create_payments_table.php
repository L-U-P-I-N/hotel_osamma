<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('received_by')->constrained('users');
            $table->decimal('amount', 10, 2);
            $table->enum('currency', ['YER','SAR','USD'])->default('YER');
            $table->enum('method', ['cash','bank_transfer','pos'])->default('cash');
            $table->string('bank_receipt_path')->nullable();
            $table->string('bank_transfer_ref')->nullable();
            $table->dateTime('payment_date');
            $table->enum('type', ['reservation','compensation','extra_service'])->default('reservation');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('payments'); }
};
