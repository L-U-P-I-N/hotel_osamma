<?php
namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Services\CheckInService;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** يتأكد أن كل صفحة مسّها التغيير تُصيَّر فعلاً بدون أخطاء Blade */
class BookingViewsRenderTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function reservation(User $user): Reservation
    {
        $room = Room::with('roomType')->where('status', 'available')->whereNull('linked_room_id')->firstOrFail();

        return app(CheckInService::class)->createCheckIn([
            'full_name'      => 'نزيل اختبار',
            'id_type'        => 'national_id',
            'id_number'      => uniqid('ID'),
            'room_id'        => $room->id,
            'check_in_date'  => now()->toDateString(),
            'check_out_date' => now()->addDays(2)->toDateString(),
            'payment_status' => 'unpaid',
        ], $user);
    }

    public function test_all_touched_pages_render_for_admin(): void
    {
        $admin       = User::role('admin')->firstOrFail();
        $employee    = User::role('receptionist')->firstOrFail();
        $reservation = $this->reservation($admin);

        $this->actingAs($admin)->get('/checkin')->assertOk();
        $this->actingAs($admin)->get('/rooms')->assertOk();
        $this->actingAs($admin)->get('/rooms/create')->assertOk();
        $this->actingAs($admin)->get('/rooms/' . $reservation->room_id . '/edit')->assertOk();
        $this->actingAs($admin)->get('/pricing')->assertOk();
        $this->actingAs($admin)->get('/reservations')->assertOk();
        $this->actingAs($admin)->get("/reservations/{$reservation->id}")->assertOk();
        $this->actingAs($admin)->get("/reservations/{$reservation->id}/edit")->assertOk();
        $this->actingAs($admin)->get("/users/{$employee->id}/permissions")
            ->assertOk()
            ->assertSee('منح خصم على الحجز', false)
            ->assertSee('إلغاء الحجز', false);
    }

    public function test_staff_check_in_page_hides_price_field_without_permission(): void
    {
        $employee = User::role('receptionist')->firstOrFail();
        $admin    = User::role('admin')->firstOrFail();

        PermissionService::toggle($employee, 'room.price.edit', false, $admin);
        $this->actingAs($employee)->get('/checkin')
            ->assertOk()
            ->assertSee('لا تملك صلاحية تعديله', false);

        PermissionService::toggle($employee, 'room.price.edit', true, $admin);
        $this->actingAs($employee)->get('/checkin')
            ->assertOk()
            ->assertSee('النطاق المسموح', false);
    }
}
