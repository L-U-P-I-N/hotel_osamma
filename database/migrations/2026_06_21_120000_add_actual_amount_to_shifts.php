<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->decimal('actual_amount', 15, 2)->nullable()->after('total_withdrawals_usd');
            $table->decimal('shortfall', 15, 2)->nullable()->after('actual_amount');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['actual_amount', 'shortfall']);
        });
    }
};
