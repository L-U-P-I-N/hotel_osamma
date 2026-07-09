<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // سعر الليلة الأولى إن اختلف عن باقي ليالي الإقامة (مثال: اتفاق على
            // 50 ألف لليلة الأولى بينما باقي الليالي 40 ألف). null يعني أنّ كل
            // الليالي بنفس السعر (السلوك الافتراضي السابق).
            $table->decimal('first_night_price', 10, 2)->nullable()->after('total_amount');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('first_night_price');
        });
    }
};
