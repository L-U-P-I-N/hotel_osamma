<?php
namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\ReservationSegment;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Shift;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingBoundsAndButtonPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User    { return User::role('admin')->firstOrFail(); }

    /**
     * CheckPermission في هذا المشروع يرفض بإعادة توجيه + رسالة خطأ في الجلسة
     * (وليس 403) للطلبات غير JSON، فنقيس الرفض بهذا المعيار.
     */
    private function assertDenied($response): void
    {
        $response->assertRedirect();
        $this->assertNotNull(session('error'), 'يُتوقع رسالة رفض في الجلسة');
    }
    private function employee(): User { return User::role('receptionist')->firstOrFail(); }

    /** نطاق واسع لنوع الغرفة حتى تتضح حدود الرفض والقبول */
    private function roomWithBounds(float $min, float $max, float $base): Room
    {
        $room = Room::with('roomType')->where('status', 'available')->firstOrFail();
        $room->roomType->update(['min_price' => $min, 'max_price' => $max, 'base_price' => $base]);
        $room->update(['price_yer' => $base]);

        return $room->fresh('roomType');
    }

    private function reservationFor(Room $room, float $price): Reservation
    {
        return Reservation::create([
            'guest_id'       => \App\Models\Guest::firstOrFail()->id,
            'room_id'        => $room->id,
            'created_by'     => $this->admin()->id,
            'check_in_date'  => now()->toDateString(),
            'check_out_date' => now()->addDays(3)->toDateString(),
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => $price * 3,
        ]);
    }

    public function test_pricing_page_is_restricted_to_the_pricing_permission(): void
    {
        $this->actingAs($this->admin())->get('/pricing')->assertOk();
        $this->assertDenied($this->actingAs($this->employee())->get('/pricing'));
    }

    public function test_admin_sets_range_and_out_of_range_prices_are_refused(): void
    {
        $admin = $this->admin();
        $room  = $this->roomWithBounds(5000, 9000, 7000);
        $type  = $room->roomType;

        $this->actingAs($admin)
            ->put("/pricing/room-types/{$type->id}", ['min_price' => 5000, 'base_price' => 7000, 'max_price' => 9000])
            ->assertSessionHasNoErrors();

        $reservation = $this->reservationFor($room, 7000);

        // فوق السقف -> مرفوض
        $this->actingAs($admin)
            ->post("/reservations/{$reservation->id}/reprice-from", [
                'from_date' => $reservation->check_in_date->toDateString(),
                'price_per_night' => 50000,
            ])->assertSessionHasErrors('price_per_night');

        // تحت الحد الأدنى -> مرفوض
        $this->actingAs($admin)
            ->post("/reservations/{$reservation->id}/reprice-from", [
                'from_date' => $reservation->check_in_date->toDateString(),
                'price_per_night' => 100,
            ])->assertSessionHasErrors('price_per_night');

        // داخل النطاق -> مقبول
        $this->actingAs($admin)
            ->post("/reservations/{$reservation->id}/reprice-from", [
                'from_date' => $reservation->check_in_date->toDateString(),
                'price_per_night' => 8000,
            ])->assertSessionHasNoErrors();
    }

    public function test_admin_rejects_an_invalid_range(): void
    {
        $type = RoomType::firstOrFail();

        $this->actingAs($this->admin())
            ->put("/pricing/room-types/{$type->id}", ['min_price' => 9000, 'base_price' => 9000, 'max_price' => 100])
            ->assertSessionHasErrors('max_price');

        $this->actingAs($this->admin())
            ->put("/pricing/room-types/{$type->id}", ['min_price' => 5000, 'base_price' => 100, 'max_price' => 7000])
            ->assertSessionHasErrors('base_price');
    }

    public function test_staff_without_price_permission_is_locked_to_the_room_default(): void
    {
        $employee = $this->employee();
        $admin    = $this->admin();
        $room     = $this->roomWithBounds(5000, 9000, 7000);
        $reservation = $this->reservationFor($room, 7000);

        PermissionService::toggle($employee, 'room.price.edit', false, $admin);
        PermissionService::toggle($employee, 'reservation.reprice', true, $admin);

        // سعر داخل النطاق لكنه مختلف عن الافتراضي -> مرفوض لانعدام الصلاحية
        $this->actingAs($employee)
            ->post("/reservations/{$reservation->id}/reprice-from", [
                'from_date' => $reservation->check_in_date->toDateString(),
                'price_per_night' => 8000,
            ])->assertSessionHasErrors('price_per_night');

        // نفس السعر الافتراضي -> مقبول
        $this->actingAs($employee)
            ->post("/reservations/{$reservation->id}/reprice-from", [
                'from_date' => $reservation->check_in_date->toDateString(),
                'price_per_night' => 7000,
            ])->assertSessionHasNoErrors();
    }

    public function test_each_reservation_button_has_its_own_permission(): void
    {
        $employee = $this->employee();
        $admin    = $this->admin();
        $room     = $this->roomWithBounds(5000, 9000, 7000);
        $reservation = $this->reservationFor($room, 7000);

        PermissionService::toggle($employee, 'reservation.cancel', false, $admin);
        $this->assertDenied($this->actingAs($employee)
            ->patch("/reservations/{$reservation->id}/cancel", ['cancellation_reason' => 'test']));
        $this->assertSame('checked_in', $reservation->fresh()->status);

        PermissionService::toggle($employee, 'reservation.discount', false, $admin);
        $this->assertDenied($this->actingAs($employee)
            ->post("/reservations/{$reservation->id}/discount", ['discount_type' => 'fixed', 'discount_value' => 100]));
        $this->assertEqualsWithDelta(0, (float) $reservation->fresh()->discount_amount, 0.01);

        PermissionService::toggle($employee, 'reservation.edit', false, $admin);
        $this->assertDenied($this->actingAs($employee)->get("/reservations/{$reservation->id}/edit"));

        // المنح يفتح الباب
        PermissionService::toggle($employee, 'reservation.edit', true, $admin);
        $this->actingAs($employee)
            ->get("/reservations/{$reservation->id}/edit")
            ->assertOk();
    }

    public function test_revoking_view_closes_all_reservation_action_permissions(): void
    {
        $employee = $this->employee();
        $admin    = $this->admin();

        PermissionService::toggle($employee, 'reservation.edit', true, $admin);
        PermissionService::toggle($employee, 'checkin.view', false, $admin);

        $this->assertFalse(PermissionService::userCan($employee->fresh(), 'reservation.edit'));
        $this->assertFalse(PermissionService::userCan($employee->fresh(), 'reservation.renew'));
    }

    public function test_locked_segment_is_editable_only_with_the_unlock_permission(): void
    {
        $employee = $this->employee();
        $admin    = $this->admin();
        $room     = $this->roomWithBounds(5000, 9000, 7000);
        $reservation = $this->reservationFor($room, 7000);

        $closedShift = Shift::create([
            'user_id'    => $admin->id,
            'shift_date' => now()->subDay()->toDateString(),
            'started_at' => now()->subDay(),
            'ended_at'   => now()->subHours(12),
            'is_closed'  => true,
        ]);

        $segment = ReservationSegment::create([
            'reservation_id'  => $reservation->id,
            'type'            => 'initial',
            'start_date'      => $reservation->check_in_date,
            'end_date'        => $reservation->check_out_date,
            'nights'          => 3,
            'price_per_night' => 7000,
            'amount'          => 21000,
            'created_by'      => $admin->id,
            'shift_id'        => $closedShift->id,
        ]);

        $this->assertTrue($segment->isLocked());

        PermissionService::toggle($employee, 'payments.create', true, $admin);
        PermissionService::toggle($employee, 'room.price.edit', true, $admin);
        PermissionService::toggle($employee, 'segment.unlock', false, $admin);

        // بلا صلاحية فكّ القفل: مرفوض
        $this->actingAs($employee)
            ->put("/reservations/segment/{$segment->id}", ['price_per_night' => 8000])
            ->assertSessionHasErrors('error');

        $this->assertEqualsWithDelta(7000, (float) $segment->fresh()->price_per_night, 0.01);

        // مع الصلاحية: يمر ويُحدَّث السعر
        PermissionService::toggle($employee, 'segment.unlock', true, $admin);
        $this->actingAs($employee)
            ->put("/reservations/segment/{$segment->id}", ['price_per_night' => 8000])
            ->assertSessionHasNoErrors();
        $this->assertNull(session('error'), 'لا يُتوقع رفض بعد منح صلاحية فكّ القفل');
        $this->assertEqualsWithDelta(8000, (float) $segment->fresh()->price_per_night, 0.01);
    }

    public function test_locked_segment_edit_still_respects_price_bounds(): void
    {
        $admin = $this->admin();
        $room  = $this->roomWithBounds(5000, 9000, 7000);
        $reservation = $this->reservationFor($room, 7000);

        $segment = ReservationSegment::create([
            'reservation_id'  => $reservation->id,
            'type'            => 'initial',
            'start_date'      => $reservation->check_in_date,
            'end_date'        => $reservation->check_out_date,
            'nights'          => 3,
            'price_per_night' => 7000,
            'amount'          => 21000,
            'created_by'      => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put("/reservations/segment/{$segment->id}", ['price_per_night' => 999999])
            ->assertSessionHasErrors('price_per_night');
    }
}
