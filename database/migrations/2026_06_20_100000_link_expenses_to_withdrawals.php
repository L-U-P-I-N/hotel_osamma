<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'bank_transfer', 'later'])
                  ->default('cash')
                  ->after('shift_id');
        });

        Schema::table('cash_withdrawals', function (Blueprint $table) {
            $table->foreignId('expense_id')
                  ->nullable()
                  ->after('shift_id')
                  ->constrained('expenses')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cash_withdrawals', function (Blueprint $table) {
            $table->dropForeign(['expense_id']);
            $table->dropColumn('expense_id');
        });
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
