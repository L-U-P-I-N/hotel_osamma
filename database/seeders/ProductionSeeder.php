<?php

namespace Database\Seeders;

use App\Models\Hotel;
use Illuminate\Database\Seeder;

/**
 * بذرة النشر: تُشغَّل تلقائياً عند كل رفع للاستضافة.
 *
 * تقتصر على البيانات المرجعية التي لا غنى عنها لتشغيل النظام (الأدوار،
 * شجرتا الحسابات، أنواع الغرف، وسجل الفندق إن غاب)، وكلها idempotent
 * فإعادة تشغيلها لا تكرّر صفاً ولا تمسّ بيانات التشغيل.
 *
 * تستبعد عمداً:
 *   - RoomSeeder / FloorSeeder: يحذفان الغرف والطوابق ثم يعيدان إنشاءها،
 *     فتضيع حالات الغرف وأسعارها وارتباط الحجوزات بها.
 *   - TestDataSeeder: يُنشئ نزلاء وحجوزات ومدفوعات وهمية بـcreate() بلا
 *     حارس، فتتراكم بيانات مالية غير حقيقية مع كل نشر.
 *   - UserSeeder: كلمات مرور افتراضية لا يصح فرضها على نظام قائم.
 *
 * تُنشأ هذه الأربعة يدوياً مرة واحدة عند التركيب الأول:
 *   php artisan db:seed --class=RoomSeeder   (وهكذا)
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // سجل الفندق: يُنشأ مرة واحدة فقط، ولا يُلمس إن كان موجوداً حتى لا
        // تُستبدل بيانات المالك الفعلية ببيانات افتراضية.
        if (Hotel::count() === 0) {
            $this->call(HotelSeeder::class);
        }

        $this->call([
            RolesSeeder::class,            // الأدوار والصلاحيات
            AccountsSeeder::class,         // دليل الحسابات التشغيلي (updateOrCreate)
            ChartOfAccountsSeeder::class,  // شجرة حسابات USALI (updateOrCreate)
            RoomTypeSeeder::class,         // أنواع الغرف ونطاقات أسعارها (firstOrCreate)
        ]);
    }
}
