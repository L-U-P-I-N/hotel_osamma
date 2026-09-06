<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\Floor;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * بذرة النشر: تُنفَّذ تلقائياً عند كل رفع للاستضافة.
 *
 * ذكية بمعنيين:
 *   1) كل بذرة تُستدعى فقط إن كان ما تُنشئه غائباً فعلاً — فالرفع المتكرر
 *      لا يعيد تركيب ما هو قائم ولا يهدر وقت النشر.
 *   2) البذور نفسها idempotent، فحتى لو استُدعيت مرتين لا تكرّر صفاً ولا
 *      تمسّ بيانات التشغيل (حالات الغرف، أسعارها، كلمات المرور).
 *
 * تستبعد TestDataSeeder وحده: يُنشئ نزلاء وحجوزات ومدفوعات وهمية بلا حارس،
 * فلا مكان له في نظام حقيقي.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // ① سجل الفندق — يُنشأ مرة واحدة، ولا تُستبدل بيانات المالك أبداً
        $this->runIfMissing('بيانات الفندق', HotelSeeder::class, fn () => Hotel::exists());

        // ② الأدوار والصلاحيات — لا بد منها قبل إنشاء المستخدمين
        $this->runIfMissing(
            'الأدوار',
            RolesSeeder::class,
            fn () => \Spatie\Permission\Models\Role::where('name', 'admin')->exists()
        );

        // ③ المستخدمون الافتراضيون — firstOrCreate فلا يمسّ كلمة مرور قائمة
        $this->runIfMissing('المستخدمين', UserSeeder::class, fn () => User::exists());

        // ④ دليل الحسابات التشغيلي المستخدم في ترحيل القيود
        $this->runIfMissing(
            'دليل الحسابات',
            AccountsSeeder::class,
            fn () => \App\Models\Account::exists()
        );

        // ⑤ شجرة حسابات USALI — تُحدَّث دائماً لأنها بيانات مرجعية بحتة،
        //    فأي حساب جديد يضيفه التحديث يصل تلقائياً دون تدخل.
        $this->call(ChartOfAccountsSeeder::class);
        $this->command?->info('  ✔ شجرة الحسابات: ' . ChartOfAccount::count() . ' حساباً');

        // ⑥ أنواع الغرف — لازمة قبل بذر الغرف
        $this->runIfMissing('أنواع الغرف', RoomTypeSeeder::class, fn () => RoomType::exists());

        // ⑦ الطوابق ثم الغرف — الهيكل الأساسي للفندق
        $this->runIfMissing('الطوابق', FloorSeeder::class, fn () => Floor::exists());
        $this->runIfMissing('الغرف',   RoomSeeder::class,  fn () => Room::exists());
    }

    /**
     * تُشغّل البذرة فقط إن كان شرط الوجود غير محقَّق.
     *
     * @param  string    $label   اسم يظهر في سجل النشر
     * @param  string    $seeder  صنف البذرة
     * @param  callable  $exists  يعيد true إذا كانت البيانات موجودة مسبقاً
     */
    private function runIfMissing(string $label, string $seeder, callable $exists): void
    {
        if ($exists()) {
            $this->command?->line("  – {$label}: موجودة مسبقاً، تم التخطي");
            return;
        }

        $this->call($seeder);
        $this->command?->info("  ✔ {$label}: تم التركيب");
    }
}
