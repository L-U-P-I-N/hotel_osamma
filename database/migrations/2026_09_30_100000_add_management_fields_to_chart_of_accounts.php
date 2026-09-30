<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * فتح شجرة الحسابات للتحرير من الواجهة (دليل الحسابات).
 *
 * حقلان يفصلان ما تملكه البذرة عمّا يُدخله المستخدم:
 *   is_system: حساب أتى من بذرة USALI. بنيته (النوع، الأب، المستوى) ملك
 *              البذرة ولا تُعدَّل من الواجهة، ولا يُحذف أبداً — لأن أكواده
 *              مثبّتة في شيفرة الترحيل. يبقى اسمه وحالته قابلين للتعديل.
 *   notes:     ملاحظة حرة على الحساب، كما في دفاتر الحسابات المتعارف عليها.
 *
 * الصفوف الموجودة وقت الترحيل كلها من البذرة بالضرورة — لم تكن هناك واجهة
 * إضافة قبل هذه الترحيلة — فتُوسم is_system صراحةً.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_active');
            $table->string('notes', 500)->nullable()->after('level');
        });

        DB::table('chart_of_accounts')->update(['is_system' => true]);
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn(['is_system', 'notes']);
        });
    }
};
