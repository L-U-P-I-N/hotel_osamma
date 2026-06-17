<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->decimal('price_yer', 12, 2)->nullable()->after('floor');
            $table->decimal('price_sar', 12, 2)->nullable()->after('price_yer');
            $table->decimal('price_usd', 12, 2)->nullable()->after('price_sar');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->enum('currency', ['YER', 'SAR', 'USD'])->default('YER')->after('paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['price_yer', 'price_sar', 'price_usd']);
        });
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
