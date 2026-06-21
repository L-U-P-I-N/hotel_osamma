<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->timestamp('salary_deducted_at')->nullable()->after('shortfall');
            $table->unsignedBigInteger('salary_deducted_by')->nullable()->after('salary_deducted_at');
        });
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['salary_deducted_at', 'salary_deducted_by']);
        });
    }
};
