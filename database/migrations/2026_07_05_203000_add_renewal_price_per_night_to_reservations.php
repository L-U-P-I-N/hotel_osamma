<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // سعر الليلة عند التجديد/التمديد — قد يختلف عن سعر الليلة الأولى المتفاوض
            // عليه (مثال: اتفاق على 35 ألف لليوم الأول بينما السعر المعتاد 40 ألف لكل
            // ليلة إضافية). null يعني: استخدم نفس سعر الليلة الأولى كما كان سابقاً.
            $table->decimal('renewal_price_per_night', 10, 2)->nullable()->after('total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('renewal_price_per_night');
        });
    }
};
