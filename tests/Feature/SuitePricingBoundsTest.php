<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuitePricingBoundsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function suiteRoom(): Room
    {
        return Room::with('roomType')->where('room_sub_type', 'suite_a')->firstOrFail();
    }

    private function reservation(Room $room, ?string $bookingType): Reservation
    {
        return Reservation::create([
            'guest_id'           => Guest::firstOrFail()->id,
            'room_id'            => $room->id,
            'suite_booking_type' => $bookingType,
            'created_by'         => $this->admin()->id,
            'check_in_date'      => today(),
            'check_out_date'     => today()->addDays(2),
            'status'             => 'checked_in',
            'payment_status'     => 'unpaid',
            'total_amount'       => 0,
        ]);
    }

    private function reprice(Reservation $reservation, float $price)
    {
        return $this->actingAs($this->admin())
            ->post("/reservations/{$reservation->id}/reprice-from", [
                'from_date'       => $reservation->check_in_date->toDateString(),
                'price_per_night' => $price,
            ]);
    }

    /** الخانتان الجديدتان تظهران لنوع له أقسام أجنحة فقط */
    public function test_pricing_page_shows_a_separate_full_suite_range(): void
    {
        $this->actingAs($this->admin())->get('/pricing')
            ->assertOk()
            ->assertSee('سعر القسم الواحد (غرفة)', false)
            ->assertSee('سعر الجناح كاملاً (غرفتان)', false)
            ->assertSee('suite_min_price', false);
    }

    /** جوهر البلاغ: نطاق الجناح الكامل صار قابلاً للتعديل ومستقلاً عن ضِعف القسم */
    public function test_admin_can_set_a_full_suite_range_that_is_not_double_the_section(): void
    {
        $type = $this->suiteRoom()->roomType;

        $this->actingAs($this->admin())
            ->put("/pricing/room-types/{$type->id}", [
                'min_price'       => 10000,
                'base_price'      => 12000,
                'max_price'       => 14000,
                // سعر عرض: أقل من مجموع القسمين (20,000–28,000)
                'suite_min_price' => 16000,
                'suite_max_price' => 22000,
            ])->assertSessionHasNoErrors();

        $type->refresh();
        $this->assertEqualsWithDelta(16000, $type->effective_suite_min_price, 0.01);
        $this->assertEqualsWithDelta(22000, $type->effective_suite_max_price, 0.01);
        // نطاق القسم لم يتأثر
        $this->assertEqualsWithDelta(10000, $type->effective_min_price, 0.01);
        $this->assertEqualsWithDelta(14000, $type->effective_max_price, 0.01);
    }

    public function test_full_suite_booking_is_judged_by_the_suite_range_only(): void
    {
        $room = $this->suiteRoom();
        $room->roomType->update([
            'min_price' => 10000, 'base_price' => 12000, 'max_price' => 14000,
            'suite_min_price' => 16000, 'suite_max_price' => 22000,
        ]);

        $suiteReservation = $this->reservation($room, 'both');

        // 18,000 خارج نطاق القسم لكنه داخل نطاق الجناح -> مقبول
        $this->reprice($suiteReservation, 18000)->assertSessionHasNoErrors();

        // 28,000 = ضِعف أعلى سعر للقسم، وكان يُقبل سابقاً — الآن مرفوض
        $this->reprice($suiteReservation, 28000)->assertSessionHasErrors('price_per_night');

        // 15,000 تحت أقل سعر للجناح -> مرفوض
        $this->reprice($suiteReservation, 15000)->assertSessionHasErrors('price_per_night');
    }

    public function test_a_suite_section_is_priced_like_a_room(): void
    {
        $room = $this->suiteRoom();
        $room->roomType->update([
            'min_price' => 10000, 'base_price' => 12000, 'max_price' => 14000,
            'suite_min_price' => 16000, 'suite_max_price' => 22000,
        ]);

        $sectionReservation = $this->reservation($room, 'a_only');

        // داخل نطاق القسم -> مقبول
        $this->reprice($sectionReservation, 13000)->assertSessionHasNoErrors();

        // داخل نطاق الجناح لكنه خارج نطاق القسم -> مرفوض
        $this->reprice($sectionReservation, 18000)->assertSessionHasErrors('price_per_night');
    }

    public function test_unset_suite_range_still_falls_back_to_double_the_section(): void
    {
        $type = $this->suiteRoom()->roomType;
        $type->update([
            'min_price' => 10000, 'max_price' => 14000,
            'suite_min_price' => 0, 'suite_max_price' => 0,
        ]);

        $type->refresh();
        $this->assertFalse($type->hasExplicitSuiteBounds());
        $this->assertEqualsWithDelta(20000, $type->effective_suite_min_price, 0.01);
        $this->assertEqualsWithDelta(28000, $type->effective_suite_max_price, 0.01);
    }

    public function test_suite_range_is_validated_and_required_only_for_suite_types(): void
    {
        $suiteType = $this->suiteRoom()->roomType;

        // نوع له أقسام أجنحة: النطاق مطلوب ومقلوبه مرفوض
        $this->actingAs($this->admin())
            ->put("/pricing/room-types/{$suiteType->id}", [
                'min_price' => 10000, 'base_price' => 12000, 'max_price' => 14000,
                'suite_min_price' => 25000, 'suite_max_price' => 20000,
            ])->assertSessionHasErrors('suite_max_price');

        $this->actingAs($this->admin())
            ->put("/pricing/room-types/{$suiteType->id}", [
                'min_price' => 10000, 'base_price' => 12000, 'max_price' => 14000,
            ])->assertSessionHasErrors('suite_min_price');

        // نوع بلا أقسام أجنحة: لا يُطالَب بها
        $plainType = RoomType::whereDoesntHave('rooms', fn($q) => $q->whereIn('room_sub_type', ['suite_a', 'suite_b']))
            ->firstOrFail();

        $this->actingAs($this->admin())
            ->put("/pricing/room-types/{$plainType->id}", [
                'min_price' => 6000, 'base_price' => 7000, 'max_price' => 9000,
            ])->assertSessionHasNoErrors();
    }
}
