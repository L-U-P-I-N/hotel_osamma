<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إعدادات عامة للنظام (مفتاح/قيمة). أول استخدام لها: شعار الفندق — يُخزَّن
 * كـdata URI داخل قاعدة البيانات لا كملف في public/، لأن قرص الاستضافة
 * (Laravel Cloud) مؤقّت ويُمحى مع كل نشر، فيضيع الشعار بعد أول تحديث.
 * تخزينه بقاعدة البيانات يجعله يظهر في الواجهة وكل تصديرات PDF معاً.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
