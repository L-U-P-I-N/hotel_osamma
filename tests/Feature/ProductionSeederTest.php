<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * بذرة النشر تعمل تلقائياً عند كل رفع، فيجب أن تكون آمنة تماماً:
 * لا تكرّر بيانات، ولا تحذف غرفاً، ولا تلمس أرقاماً مالية.
 */
class ProductionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /** إعادة التشغيل لا تغيّر أي عدد */
    public function test_running_it_repeatedly_changes_nothing(): void
    {
        $before = [
            'hotels'       => Hotel::count(),
            'rooms'        => Room::count(),
            'guests'       => Guest::count(),
            'reservations' => Reservation::count(),
            'payments'     => Payment::count(),
            'coa'          => ChartOfAccount::count(),
        ];

        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        $this->assertSame($before['hotels'],       Hotel::count(),          'تكرر سجل الفندق');
        $this->assertSame($before['rooms'],        Room::count(),           'تغيّر عدد الغرف');
        $this->assertSame($before['guests'],       Guest::count(),          'أُضيف نزلاء وهميون');
        $this->assertSame($before['reservations'], Reservation::count(),    'أُضيفت حجوزات وهمية');
        $this->assertSame($before['payments'],     Payment::count(),        'أُضيفت مدفوعات وهمية');
        $this->assertSame($before['coa'],          ChartOfAccount::count(), 'تكررت حسابات الشجرة');
    }

    /** لا تمسّ حالة الغرف ولا أسعارها — كان RoomSeeder يحذفها ويعيد إنشاءها */
    public function test_it_preserves_room_state_and_prices(): void
    {
        $room = Room::firstOrFail();
        $room->update(['status' => 'maintenance', 'price_yer' => 99999]);

        $this->seed(ProductionSeeder::class);

        $room->refresh();
        $this->assertSame('maintenance', $room->status);
        $this->assertEqualsWithDelta(99999, (float) $room->price_yer, 0.01);
    }

    /** لا تستبدل بيانات الفندق التي أدخلها المالك */
    public function test_it_does_not_overwrite_an_existing_hotel_record(): void
    {
        $hotel = Hotel::firstOrFail();
        $hotel->update(['name' => 'اسم المالك الفعلي', 'phone' => '0777000111']);

        $this->seed(ProductionSeeder::class);

        $hotel->refresh();
        $this->assertSame('اسم المالك الفعلي', $hotel->name);
        $this->assertSame('0777000111', $hotel->phone);
        $this->assertSame(1, Hotel::count());
    }

    /** تُنشئ البيانات المرجعية اللازمة للتشغيل */
    public function test_it_installs_the_reference_data(): void
    {
        $this->seed(ProductionSeeder::class);

        $this->assertGreaterThan(0, \Spatie\Permission\Models\Role::where('name', 'admin')->count());
        $this->assertGreaterThanOrEqual(80, ChartOfAccount::count());
        $this->assertTrue(ChartOfAccount::where('code', '4110')->exists());
        $this->assertGreaterThan(0, \App\Models\RoomType::count());
    }

    /** سكربت النشر يستدعي البذرة الآمنة لا البذرة الكاملة */
    public function test_deploy_script_uses_the_safe_seeder(): void
    {
        $script = file_get_contents(base_path('docker/deploy.sh'));

        $this->assertStringContainsString('php artisan migrate --force', $script);
        $this->assertStringContainsString('--class=ProductionSeeder', $script);
        $this->assertStringNotContainsString('db:seed --force' . PHP_EOL, $script);

        $railway = json_decode(file_get_contents(base_path('railway.json')), true);
        $this->assertStringContainsString('deploy.sh', $railway['deploy']['startCommand']);
        $this->assertStringNotContainsString('db:seed --force', $railway['deploy']['startCommand']);
    }
}
