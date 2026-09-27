<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ملاحظة تُكتب لحظة تسجيل الخروج (حالة الغرفة، سبب المغادرة المبكرة، وعد
 * بالعودة...). حقل مستقل عن notes العام حتى تظهر في كشوف النزلاء — الموجودين
 * والمغادرين — بوصفها ملاحظة الخروج لا ملاحظة الحجز.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->text('checkout_notes')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('checkout_notes');
        });
    }
};
