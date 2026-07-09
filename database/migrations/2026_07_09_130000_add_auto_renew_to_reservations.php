<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // التجديد التلقائي: عند تفعيله يُحتسب يوم جديد تلقائياً كلما تجاوز الوقت
            // الساعة 1 ظهراً من تاريخ الخروج (لإقامات النزلاء المستمرين).
            $table->boolean('auto_renew')->default(false)->after('renewal_price_per_night');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('auto_renew');
        });
    }
};
