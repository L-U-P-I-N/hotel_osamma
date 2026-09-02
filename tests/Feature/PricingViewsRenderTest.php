<?php
namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** كل صفحة مسّها التغيير يجب أن تُصيَّر فعلاً */
class PricingViewsRenderTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_touched_pages_render(): void
    {
        $admin    = User::role('admin')->firstOrFail();
        $employee = User::role('receptionist')->firstOrFail();
        $room     = Room::where('status', 'available')->firstOrFail();

        $reservation = Reservation::create([
            'guest_id'       => \App\Models\Guest::firstOrFail()->id,
            'room_id'        => $room->id,
            'created_by'     => $admin->id,
            'check_in_date'  => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => 16000,
        ]);

        $this->actingAs($admin)->get('/pricing')->assertOk()->assertSee('نطاق سعر الليلة', false);
        $this->actingAs($admin)->get('/checkin')->assertOk();
        $this->actingAs($admin)->get("/reservations/{$reservation->id}")->assertOk();
        $this->actingAs($admin)->get("/reservations/{$reservation->id}/edit")->assertOk();

        // صفحة الصلاحيات تعرض المفاتيح الجديدة كي يتحكم بها المدير
        $this->actingAs($admin)->get("/users/{$employee->id}/permissions")
            ->assertOk()
            ->assertSee('إلغاء الحجز', false)
            ->assertSee('منح خصم على الحجز', false)
            ->assertSee('تعديل فترة تخصّ وردية مقفلة', false)
            ->assertSee('إعدادات التسعير', false);
    }

    public function test_check_in_hides_the_price_override_from_staff_without_permission(): void
    {
        $employee = User::role('receptionist')->firstOrFail();
        $admin    = User::role('admin')->firstOrFail();

        \App\Services\PermissionService::toggle($employee, 'room.price.edit', false, $admin);
        $this->actingAs($employee)->get('/checkin')
            ->assertOk()
            ->assertDontSee('تعديل السعر (للتفاوض)', false);

        \App\Services\PermissionService::toggle($employee, 'room.price.edit', true, $admin);
        $this->actingAs($employee)->get('/checkin')
            ->assertOk()
            ->assertSee('تعديل السعر (للتفاوض)', false)
            ->assertSee('النطاق المسموح', false)
            ->assertDontSee('لا يوجد حد أقصى', false);
    }
}
