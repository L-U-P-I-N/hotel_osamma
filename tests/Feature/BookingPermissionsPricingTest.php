<?php
namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Services\CheckInService;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPermissionsPricingTest extends TestCase
{
    use RefreshDatabase;

    /** الاختبارات تعتمد على الأدوار والغرف التي ينشئها الـ seeders */
    protected bool $seed = true;

    private function employee(): User { return User::role('receptionist')->firstOrFail(); }
    private function admin(): User    { return User::role('admin')->firstOrFail(); }

    private function makeReservation(User $user, array $extra = []): Reservation
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
        ] + $extra, $user);
    }

    public function test_pricing_page_is_admin_only(): void
    {
        $this->actingAs($this->admin())->get('/pricing')->assertOk();
        $this->actingAs($this->employee())->get('/pricing')->assertForbidden();
    }

    public function test_reservation_detail_page_renders(): void
    {
        $reservation = $this->makeReservation($this->admin());
        $this->actingAs($this->admin())
            ->get("/reservations/{$reservation->id}")
            ->assertOk()
            ->assertSee('سعر الليلة المعتمد', false);
    }

    public function test_each_reservation_button_has_its_own_permission(): void
    {
        $employee    = $this->employee();
        $admin       = $this->admin();
        $reservation = $this->makeReservation($admin);

        // cancel is off by default for staff
        PermissionService::toggle($employee, 'reservation.cancel', false, $admin);
        $this->actingAs($employee)
            ->patch("/reservations/{$reservation->id}/cancel")
            ->assertForbidden();

        // ...and works once the admin grants it
        PermissionService::toggle($employee, 'reservation.cancel', true, $admin);
        $this->actingAs($employee)
            ->patch("/reservations/{$reservation->id}/cancel")
            ->assertRedirect();
        $this->assertSame('cancelled', $reservation->fresh()->status);
    }

    public function test_discount_is_blocked_without_permission_and_capped_by_ceiling(): void
    {
        $employee    = $this->employee();
        $admin       = $this->admin();
        $reservation = $this->makeReservation($admin);
        Hotel::first()->update(['max_discount_percent' => 10]);

        PermissionService::toggle($employee, 'reservation.discount', false, $admin);
        $this->actingAs($employee)
            ->post("/reservations/{$reservation->id}/discount", ['amount' => 50, 'reason' => 'test'])
            ->assertForbidden();

        PermissionService::toggle($employee, 'reservation.discount', true, $admin);

        $gross = (float) $reservation->total_amount;

        // above the 10% ceiling -> rejected
        $this->actingAs($employee)
            ->post("/reservations/{$reservation->id}/discount", ['amount' => $gross * 0.5, 'reason' => 'كثير'])
            ->assertSessionHasErrors('amount');

        // within the ceiling -> applied, total reduced
        $allowed = round($gross * 0.10, 2);
        $this->actingAs($employee)
            ->post("/reservations/{$reservation->id}/discount", ['amount' => $allowed, 'reason' => 'نزيل دائم'])
            ->assertSessionHasNoErrors();

        $reservation->refresh();
        $this->assertEqualsWithDelta($allowed, (float) $reservation->discount_amount, 0.01);
        $this->assertEqualsWithDelta($gross - $allowed, (float) $reservation->total_amount, 0.01);
        $this->assertEqualsWithDelta($gross, $reservation->gross_amount, 0.01);
    }

    public function test_admin_can_set_price_range_and_out_of_range_edit_is_rejected(): void
    {
        $admin       = $this->admin();
        $reservation = $this->makeReservation($admin);
        $type        = $reservation->room->roomType;

        $this->actingAs($admin)
            ->put("/pricing/room-types/{$type->id}", [
                'min_price'  => 5000,
                'base_price' => 6000,
                'max_price'  => 7000,
            ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(5000, $type->fresh()->effective_min_price, 0.01);

        // price above the admin's ceiling is refused
        $this->actingAs($admin)
            ->put("/reservations/{$reservation->id}", [
                'check_in_date'  => $reservation->check_in_date->toDateString(),
                'check_out_date' => $reservation->check_out_date->toDateString(),
                'nightly_price'  => 99999,
            ])->assertSessionHasErrors('nightly_price');

        // price inside the range is accepted and the total recomputed on the server
        $this->actingAs($admin)
            ->put("/reservations/{$reservation->id}", [
                'check_in_date'  => $reservation->check_in_date->toDateString(),
                'check_out_date' => $reservation->check_out_date->toDateString(),
                'nightly_price'  => 6500,
            ])->assertSessionHasNoErrors();

        $reservation->refresh();
        $this->assertEqualsWithDelta(6500, (float) $reservation->nightly_price, 0.01);
        $this->assertEqualsWithDelta(6500 * $reservation->nights, (float) $reservation->total_amount, 0.01);
    }

    public function test_admin_rejects_invalid_price_range(): void
    {
        $type = Room::with('roomType')->firstOrFail()->roomType;

        // max below min
        $this->actingAs($this->admin())
            ->put("/pricing/room-types/{$type->id}", ['min_price' => 9000, 'base_price' => 9000, 'max_price' => 100])
            ->assertSessionHasErrors('max_price');

        // base outside the range
        $this->actingAs($this->admin())
            ->put("/pricing/room-types/{$type->id}", ['min_price' => 5000, 'base_price' => 100, 'max_price' => 7000])
            ->assertSessionHasErrors('base_price');
    }
}
