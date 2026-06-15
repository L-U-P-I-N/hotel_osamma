<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cash_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->date('shift_date');
            $table->decimal('total_received', 10, 2)->default(0);
            $table->decimal('total_withdrawals', 10, 2)->default(0);
            $table->decimal('net_balance', 10, 2)->default(0);
            $table->text('employee_signature')->nullable();
            $table->text('admin_signature')->nullable();
            $table->enum('status', ['open','locked'])->default('open');
            $table->foreignId('locked_by')->nullable()->constrained('users');
            $table->dateTime('locked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('cash_settlements'); }
};
