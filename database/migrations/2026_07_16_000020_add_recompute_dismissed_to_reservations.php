<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * علم يُخفي تنبيه "تصحيح: إعادة احتساب الفترات والإجمالي" لحجزٍ بعينه. ميزة
     * إعادة الاحتساب مفيدة لبعض الحجوزات فقط، لكنها تُظهر تنبيهاً كلما اختلف تفصيل
     * الفترات المحفوظ عن صيغة حساب الليالي الحالية — وهذا مقصود لحجوزات سُعّرت
     * يدوياً بأسعار تجديد مختلفة عبر الزمن (لا يجوز توحيدها كلها بآخر سعر). فمتى
     * أخفى الموظف التنبيه لحجزٍ بقي مخفياً حتى يُعيد إظهاره.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->boolean('recompute_dismissed')->default(false)->after('auto_renew');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('recompute_dismissed');
        });
    }
};
