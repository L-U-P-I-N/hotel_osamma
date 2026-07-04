<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('auto_renewed_at')->nullable()->after('cancelled_at');
            $table->unsignedInteger('auto_renew_extra_nights')->nullable()->after('auto_renewed_at');
            $table->boolean('auto_renew_acknowledged')->default(true)->after('auto_renew_extra_nights');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['auto_renewed_at', 'auto_renew_extra_nights', 'auto_renew_acknowledged']);
        });
    }
};
