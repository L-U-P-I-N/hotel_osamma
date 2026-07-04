<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('processed_by')->constrained()->nullOnDelete();
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->decimal('total_refunds_yer', 12, 2)->default(0)->after('total_withdrawals_usd');
            $table->decimal('total_refunds_sar', 12, 2)->default(0)->after('total_refunds_yer');
            $table->decimal('total_refunds_usd', 12, 2)->default(0)->after('total_refunds_sar');
        });
    }

    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shift_id');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['total_refunds_yer', 'total_refunds_sar', 'total_refunds_usd']);
        });
    }
};
