<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->date('shift_date');
            $table->enum('shift_type', ['morning','evening','night']);
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->dateTime('closed_at')->nullable();
            $table->decimal('total_received_yer', 12, 2)->default(0);
            $table->decimal('total_received_sar', 10, 2)->default(0);
            $table->decimal('total_received_usd', 10, 2)->default(0);
            $table->decimal('total_withdrawals_yer', 12, 2)->default(0);
            $table->decimal('total_withdrawals_sar', 10, 2)->default(0);
            $table->decimal('total_withdrawals_usd', 10, 2)->default(0);
            $table->text('employee_signature')->nullable();
            $table->text('admin_signature')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
