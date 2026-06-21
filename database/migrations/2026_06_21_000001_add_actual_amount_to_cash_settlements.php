<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cash_settlements', function (Blueprint $table) {
            $table->decimal('actual_amount', 15, 2)->nullable()->after('net_balance');
            $table->decimal('shortfall', 15, 2)->nullable()->after('actual_amount');
        });
    }

    public function down(): void
    {
        Schema::table('cash_settlements', function (Blueprint $table) {
            $table->dropColumn(['actual_amount', 'shortfall']);
        });
    }
};
