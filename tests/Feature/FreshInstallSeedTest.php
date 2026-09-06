<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Floor;
use App\Models\Hotel;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * استضافة جديدة تماماً: قاعدة مُرحَّلة بلا أي بذرة.
 * يجب أن يُركّب النشر النظام كاملاً دون أي أمر يدوي.
 *
 * يستخدم DatabaseMigrations لا RefreshDatabase: الأخير يلفّ الاختبار في
 * معاملة، فلا تظهر القاعدة فارغة فعلاً كما هي على استضافة جديدة.
 */
class FreshInstallSeedTest extends TestCase
{
    use DatabaseMigrations;

    public function test_a_blank_host_is_fully_installed_by_the_deploy_seeder(): void
    {
        // DatabaseMigrations تُرحّل القاعدة مرة واحدة فعلياً (لا داخل معاملة)
        // فتحاكي استضافة جديدة تماماً بلا حاجة لاستدعاء migrate:fresh يدوياً.

        // لا شيء قبل النشر — عدا الطوابق التي يملؤها ترحيل
        $this->assertSame(0, Hotel::count());
        $this->assertSame(0, User::count());
        $this->assertSame(0, Room::count());
        $this->assertSame(0, ChartOfAccount::count());

        $this->seed(ProductionSeeder::class);

        $this->assertSame(1, Hotel::count(), 'سجل الفندق');
        $this->assertGreaterThan(0, User::count(), 'المستخدمون');
        $this->assertGreaterThan(0, RoomType::count(), 'أنواع الغرف');
        $this->assertGreaterThan(0, Floor::count(), 'الطوابق');
        $this->assertGreaterThan(0, Room::count(), 'الغرف');
        $this->assertGreaterThanOrEqual(80, ChartOfAccount::count(), 'شجرة الحسابات');

        // مدير جاهز للدخول بدوره
        $admin = User::where('username', 'admin')->first();
        $this->assertNotNull($admin, 'حساب المدير');
        $this->assertTrue($admin->hasRole('admin'));

        // شجرة الحسابات تظهر فعلاً في صفحتها — وهي شكوى المالك الأصلية
        $this->actingAs($admin)->get('/accounting/chart-of-accounts')
            ->assertOk()
            ->assertSee('إيرادات الغرف', false)
            ->assertDontSee('0 حساباً', false);
    }

    /** إعادة النشر على نظام مركَّب لا تغيّر شيئاً */
    public function test_a_second_deploy_changes_nothing(): void
    {
        $this->seed(ProductionSeeder::class);

        $before = [
            Hotel::count(), User::count(), Room::count(),
            Floor::count(), RoomType::count(), ChartOfAccount::count(),
        ];

        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        $this->assertSame($before, [
            Hotel::count(), User::count(), Room::count(),
            Floor::count(), RoomType::count(), ChartOfAccount::count(),
        ]);
    }
}
