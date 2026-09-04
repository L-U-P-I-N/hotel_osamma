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

    /**
     * سيناريو المستخدم الحقيقي: أقسام الجناح مُصنَّفة "غرفة عادية" (لأنها غرف
     * أصلاً)، فنطاق الجناح يجب أن يُطبَّق رغم ذلك — كان يُتجاهَل تماماً ويُستبدل
     * بضِعف نطاق الغرفة العادية.
     */
    public function test_suite_range_applies_even_when_sections_are_typed_as_regular_rooms(): void
    {
        $room = $this->suiteRoom();
        $regular = RoomType::where('name', 'غرفة عادية')->firstOrFail();
        $regular->update(['min_price' => 30000, 'base_price' => 40000, 'max_price' => 60000]);
        $room->update(['room_type_id' => $regular->id]);

        // المدير يضبط نطاق الجناح على مستوى الفندق
        $this->actingAs($this->admin())->put('/pricing/suite-range', [
            'suite_min_price' => 50000,
            'suite_max_price' => 200000,
        ])->assertSessionHasNoErrors();

        $suite = $this->reservation($room->fresh(), 'both');

        // 50,000 كان يُرفض (ضِعف نطاق الغرفة = 60,000–120,000) — الآن يُقبل
        $this->reprice($suite, 50000)->assertSessionHasNoErrors();
        $this->reprice($suite, 180000)->assertSessionHasNoErrors();

        // خارج نطاق الجناح -> مرفوض
        $this->reprice($suite, 40000)->assertSessionHasErrors('price_per_night');
        $this->reprice($suite, 250000)->assertSessionHasErrors('price_per_night');
    }

    /** القسم الواحد يبقى على نطاق نوعه — غرفة كأي غرفة */
    public function test_a_single_section_still_uses_its_room_type_range(): void
    {
        $room = $this->suiteRoom();
        $regular = RoomType::where('name', 'غرفة عادية')->firstOrFail();
        $regular->update(['min_price' => 30000, 'base_price' => 40000, 'max_price' => 60000]);
        $room->update(['room_type_id' => $regular->id]);

        \App\Models\Setting::set(\App\Models\Setting::SUITE_MIN_PRICE, '50000');
        \App\Models\Setting::set(\App\Models\Setting::SUITE_MAX_PRICE, '200000');

        $section = $this->reservation($room->fresh(), 'a_only');

        $this->reprice($section, 45000)->assertSessionHasNoErrors();
        // داخل نطاق الجناح لكنه خارج نطاق الغرفة -> مرفوض
        $this->reprice($section, 150000)->assertSessionHasErrors('price_per_night');
    }

    /** ما لم يُضبط نطاق الجناح يسقط على ضِعف نطاق القسم — سلوك سابق محفوظ */
    public function test_unset_suite_range_falls_back_to_double_the_section(): void
    {
        $room = $this->suiteRoom();
        $room->roomType->update(['min_price' => 10000, 'base_price' => 12000, 'max_price' => 14000]);

        $this->assertNull(\App\Models\Setting::suitePriceRange());

        $suite = $this->reservation($room, 'both');
        $this->reprice($suite, 24000)->assertSessionHasNoErrors();
        $this->reprice($suite, 29000)->assertSessionHasErrors('price_per_night');
    }

    public function test_pricing_page_exposes_one_suite_range_card(): void
    {
        $this->actingAs($this->admin())->get('/pricing')
            ->assertOk()
            ->assertSee('نطاق سعر الجناح كاملاً (غرفتان)', false)
            ->assertSee('suite_min_price', false);
    }

    public function test_suite_range_is_validated(): void
    {
        $this->actingAs($this->admin())
            ->put('/pricing/suite-range', ['suite_min_price' => 90000, 'suite_max_price' => 1000])
            ->assertSessionHasErrors('suite_max_price');

        $this->actingAs($this->admin())
            ->put('/pricing/suite-range', ['suite_min_price' => 0, 'suite_max_price' => 1000])
            ->assertSessionHasErrors('suite_min_price');
    }

    /** سعر الجناح الافتراضي = مجموع القسمين، والسعر اليدوي القديم لا يُقرأ */
    public function test_full_suite_default_price_is_the_sum_of_sections(): void
    {
        $a = $this->suiteRoom();
        $b = $a->suitePartner();
        $this->assertNotNull($b);

        $a->update(['price_yer' => 9000, 'suite_price_yer' => 50000]);
        $b->update(['price_yer' => 9000]);

        $this->assertEqualsWithDelta(18000, $a->fresh()->fullSuitePrice(), 0.01,
            'السعر اليدوي القديم يجب أن يُتجاهل');
    }
}
