<?php

use Illuminate\Database\Migrations\Migration;

/**
 * حساب المبالغ المتبقية للنزلاء.
 *
 * أُنشئ هذا الحساب أصلاً في الشجرة القديمة (جدول accounts) بالكود 2300 — وهو
 * كود يعني «الضرائب المستحقة» في شجرة USALI، فكان تصادماً ينتظر أن يظهر.
 * بعد توحيد الشجرة صار مكانه الصحيح في chart_of_accounts بالكود **2230**
 * (أرصدة نزلاء دائنة)، وهو موجود أصلاً ضمن ChartOfAccountsSeeder.
 *
 * تُركت الترحيلة بلا أثر لا محذوفة: حذف ملفها يكسر تسلسل الترحيلات على قواعد
 * شغّلتها فعلاً، وتنفيذها من جديد يُعيد إحياء حسابٍ في شجرة مجمَّدة.
 */
return new class extends Migration
{
    public function up(): void
    {
        // لا أثر — الحساب صار 2230 في شجرة USALI (انظر ChartOfAccountsSeeder)
    }

    public function down(): void
    {
        // لا أثر
    }
};
