<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->dateTime('actual_check_out')->nullable();
            $table->string('origin')->nullable();
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['confirmed','checked_in','checked_out','cancelled'])->default('confirmed');
            $table->enum('payment_status', ['unpaid','partial','paid','deferred'])->default('unpaid');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->foreignId('admin_approval_id')->nullable()->constrained('users');
            $table->boolean('government_exported')->default(false);
            $table->dateTime('government_exported_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('reservations'); }
};
