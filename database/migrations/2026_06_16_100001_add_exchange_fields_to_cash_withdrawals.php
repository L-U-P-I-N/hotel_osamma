<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cash_withdrawals', function (Blueprint $table) {
            $table->string('withdrawal_type', 20)->default('expense')->after('notes');
            $table->string('exchange_to_currency', 10)->nullable()->after('withdrawal_type');
            $table->decimal('exchange_to_amount', 12, 2)->nullable()->after('exchange_to_currency');
        });
    }

    public function down(): void
    {
        Schema::table('cash_withdrawals', function (Blueprint $table) {
            $table->dropColumn(['withdrawal_type', 'exchange_to_currency', 'exchange_to_amount']);
        });
    }
};
