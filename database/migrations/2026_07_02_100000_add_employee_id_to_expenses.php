<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ربط المصروف بموظف الفندق (اختياري): عندما يُصرف مبلغ لموظف
     * يُقيَّد عليه كمسحوبات ويُخصم لاحقاً من راتبه الشهري.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('paid_by')
                ->constrained('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
