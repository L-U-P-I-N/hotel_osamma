<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomStatusFromCheckInTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User       { return User::role('admin')->firstOrFail(); }
    private function receptionist(): User { return User::role('receptionist')->firstOrFail(); }

    private function openShift(User $u): Shift
    {
        return Shift::create([
            'user_id' => $u->id, 'shift_date' => today(),
            'started_at' => now()->subHour(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);
    }

    /** توحيد السعر بالنوع أُزيل بالكامل */
    public function test_bulk_price_feature_is_gone(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('rooms.bulkPrice'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('rooms.bulkDelete'));

        $this->actingAs($this->admin())->get('/rooms')
            ->assertOk()
            ->assertDontSee('توحيد سعر الغرف', false);

        // المسار لم يعد يقبل POST، والأهم: لا سعر يتغيّر
        $room = Room::where('room_sub_type', 'regular')->firstOrFail();
        $before = (float) $room->price_yer;

        $this->actingAs($this->admin())
            ->post('/rooms/bulk-price', ['sub_type' => 'regular', 'price_yer' => 999]);

        $this->assertEqualsWithDelta($before, (float) $room->fresh()->price_yer, 0.01,
            'لا يجوز أن يبقى أي منفذ لتوحيد الأسعار');
    }

    /** الغرف غير المتاحة تظهر في شاشة التسجيل مع أزرار تغيير الحالة */
    public function test_check_in_lists_blocked_rooms_with_status_controls(): void
    {
        $room = Room::where('status', 'available')->firstOrFail();
        $room->update(['status' => 'under_inspection']);

        $this->actingAs($this->receptionist())->get('/checkin')
            ->assertOk()
            ->assertSee('غرف غير متاحة', false)
            ->assertSee($room->room_number, false)
            ->assertSee('تحت الفحص', false);
    }

    /** التحويل إلى متاحة من شاشة التسجيل يعيد الموظف إليها والغرفة تصير قابلة للحجز */
    public function test_receptionist_frees_a_room_and_returns_to_check_in(): void
    {
        $room = Room::where('status', 'available')->firstOrFail();
        $room->update(['status' => 'under_inspection']);

        $this->actingAs($this->receptionist())
            ->post("/rooms/{$room->id}/status", ['status' => 'available', 'redirect_to' => 'checkin'])
            ->assertRedirect(route('checkin.create'));

        $this->assertSame('available', $room->fresh()->status);
    }

    public function test_room_can_be_sent_to_maintenance_and_back(): void
    {
        $room = Room::where('status', 'available')->firstOrFail();

        $this->actingAs($this->receptionist())
            ->post("/rooms/{$room->id}/status", ['status' => 'maintenance', 'redirect_to' => 'checkin']);
        $this->assertSame('maintenance', $room->fresh()->status);

        $this->actingAs($this->receptionist())
            ->post("/rooms/{$room->id}/status", ['status' => 'under_inspection', 'redirect_to' => 'checkin']);
        $this->assertSame('under_inspection', $room->fresh()->status);

        $this->actingAs($this->receptionist())
            ->post("/rooms/{$room->id}/status", ['status' => 'available', 'redirect_to' => 'checkin']);
        $this->assertSame('available', $room->fresh()->status);
    }

    /** الحماية القائمة تبقى: غرفة بها نزيل لا تُغيَّر حالتها */
    public function test_occupied_room_status_is_still_protected(): void
    {
        $room = Room::where('status', 'available')->firstOrFail();
        Reservation::create([
            'guest_id' => Guest::firstOrFail()->id, 'room_id' => $room->id,
            'created_by' => $this->admin()->id,
            'check_in_date' => today(), 'check_out_date' => today()->addDays(2),
            'status' => 'checked_in', 'payment_status' => 'unpaid', 'total_amount' => 1000,
        ]);
        $room->update(['status' => 'occupied']);

        $this->actingAs($this->receptionist())
            ->post("/rooms/{$room->id}/status", ['status' => 'available', 'redirect_to' => 'checkin'])
            ->assertSessionHasErrors('status');

        $this->assertSame('occupied', $room->fresh()->status);
    }

    /** بلا صلاحية تغيير الحالة لا تظهر اللوحة ولا يُقبل الطلب */
    public function test_staff_without_maintenance_permission_cannot_change_status(): void
    {
        $employee = $this->receptionist();
        PermissionService::toggle($employee, 'rooms.maintenance', false, $this->admin());
        PermissionService::toggle($employee, 'rooms.edit', false, $this->admin());

        $room = Room::where('status', 'available')->firstOrFail();
        $room->update(['status' => 'under_inspection']);

        $this->actingAs($employee)->get('/checkin')
            ->assertOk()
            ->assertDontSee('غرف غير متاحة', false);

        $response = $this->actingAs($employee)
            ->post("/rooms/{$room->id}/status", ['status' => 'available', 'redirect_to' => 'checkin']);
        $response->assertRedirect();
        $this->assertNotNull(session('error'));
        $this->assertSame('under_inspection', $room->fresh()->status);
    }
}
