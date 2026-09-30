<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\ReservationSegment;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * اختيار غرفة فترة المحاسبة عند تصحيح سعر تجديد.
 *
 * كانت القائمة تعرض غرف الفندق كلها والمتحكّم يقبل أيّها — فيُنسب تجديدٌ إلى
 * غرفة لم يدخلها النزيل قطّ، وتخرج فاتورة الغرفة الجزئية بأرقام غرفة أخرى دون
 * أن يُخطئ شيء. وحين لا ينتقل النزيل أصلاً فلا خيار، فعرض قائمةٍ من عنصر واحد
 * سؤالٌ بلا جواب.
 */
class SegmentRoomChoiceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::role('admin')->firstOrFail();
    }

    /**
     * حجز في غرفة واحدة، مع فترة حجز أولي عليها.
     *
     * السعر يُؤخذ من الغرفة نفسها لا رقماً ثابتاً: قاعدة حدود السعر ترفض ما يخرج
     * عن نطاق نوع الغرفة. والإجمالي يُساوي مجموع الفترات لأن الشاشة لا تعرض
     * تفصيل الفترات إلا حين يتطابقان.
     */
    private function reservation(Room $room): Reservation
    {
        $guest = Guest::create([
            'full_name' => 'نزيل الفترات', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '05' . random_int(1000000, 9999999),
        ]);

        $reservation = Reservation::create([
            'guest_id' => $guest->id, 'room_id' => $room->id,
            'created_by' => $this->admin()->id,
            'check_in_date' => today()->subDays(4), 'check_out_date' => today()->addDay(),
            'status' => 'checked_in', 'payment_status' => 'unpaid',
            'total_amount' => 2 * $room->priceFor('YER'),
        ]);

        $this->segment($reservation, $room, today()->subDays(4), today()->subDays(2), 2, $room->priceFor('YER'));

        return $reservation;
    }

    /** يُضيف فترة ويُبقي الإجمالي مساوياً لمجموع الفترات */
    private function addSegment(Reservation $r, Room $room, $from, $to, int $nights): void
    {
        $price = $room->priceFor('YER');
        $this->segment($r, $room, $from, $to, $nights, $price);
        $r->update(['total_amount' => (float) $r->total_amount + $nights * $price]);
    }

    private function segment(Reservation $r, ?Room $room, $from, $to, int $nights, float $price): ReservationSegment
    {
        return ReservationSegment::create([
            'reservation_id'  => $r->id,
            'room_id'         => $room?->id,
            'type'            => $r->segments()->count() === 0 ? 'initial' : 'renewal',
            'start_date'      => $from,
            'end_date'        => $to,
            'nights'          => $nights,
            'price_per_night' => $price,
            'amount'          => $nights * $price,
            'created_by'      => $this->admin()->id,
        ]);
    }

    private function rooms(int $count): array
    {
        return Room::orderBy('room_number')->take($count)->get()->all();
    }

    // ───────────────── الخيارات ─────────────────

    /** نزيل لم ينتقل: غرفة واحدة فقط، فلا شيء يُختار */
    public function test_a_guest_who_never_moved_has_nothing_to_choose(): void
    {
        [$room] = $this->rooms(1);
        $reservation = $this->reservation($room);

        $this->assertSame([$room->id], $reservation->stayedRooms()->pluck('id')->all());
    }

    /**
     * نزيل انتقل: تُعرض غرفه هو وحدها — لا غرف الفندق كلها.
     * مثال المالك: كان في 204 و208 وهو الآن في 302.
     */
    public function test_a_moved_guest_sees_only_the_rooms_he_stayed_in(): void
    {
        [$first, $second, $current] = $this->rooms(3);

        $reservation = $this->reservation($first);
        $this->addSegment($reservation, $second, today()->subDays(2), today()->subDay(), 1);
        $reservation->update(['room_id' => $current->id]);

        $stayed = $reservation->fresh()->stayedRooms();

        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id, $current->id],
            $stayed->pluck('id')->all()
        );
        // وغرف الفندق الأخرى ليست منها
        $this->assertLessThan(Room::count(), $stayed->count(), 'القائمة ما زالت تشمل غرف الفندق كلها');
    }

    // ───────────────── الواجهة ─────────────────

    public function test_the_field_is_hidden_when_the_guest_never_moved(): void
    {
        [$room] = $this->rooms(1);
        $reservation = $this->reservation($room);

        $this->actingAs($this->admin())
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertDontSee('الغرفة التي كان فيها النزيل في هذه الفترة', false);
    }

    public function test_the_field_appears_with_the_guest_rooms_once_he_moved(): void
    {
        [$first, $second, $current] = $this->rooms(3);
        $other = Room::whereNotIn('id', [$first->id, $second->id, $current->id])->firstOrFail();

        $reservation = $this->reservation($first);
        $this->addSegment($reservation, $second, today()->subDays(2), today()->subDay(), 1);
        $reservation->update(['room_id' => $current->id]);

        $html = $this->actingAs($this->admin())
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('الغرفة التي كان فيها النزيل في هذه الفترة', false)
            ->getContent();

        $this->assertStringContainsString('<option value="' . $first->id . '">', $html);
        $this->assertStringContainsString('<option value="' . $second->id . '">', $html);
        $this->assertStringNotContainsString(
            '<option value="' . $other->id . '">غرفة ' . $other->room_number . '</option>',
            $html,
            'القائمة تعرض غرفة لم يدخلها النزيل'
        );
    }

    // ───────────────── الحرس على الخادم ─────────────────

    /** تقييد الخيارات في الصفحة لا يمنع طلباً يصل من خارجها */
    public function test_a_room_outside_the_reservation_is_refused(): void
    {
        [$first, $second] = $this->rooms(2);
        $reservation = $this->reservation($first);
        $reservation->update(['room_id' => $second->id]);

        $segment = $reservation->segments()->firstOrFail();
        $stranger = Room::whereNotIn('id', [$first->id, $second->id])->firstOrFail();

        $this->actingAs($this->admin())
            ->put(route('reservations.updateSegment', $segment), [
                'price_per_night' => $second->priceFor('YER'),
                'room_id'         => $stranger->id,
            ])
            ->assertSessionHasErrors('room_id');

        $this->assertSame($first->id, $segment->fresh()->room_id, 'نُسبت الفترة لغرفة غريبة');
    }

    public function test_a_room_the_guest_stayed_in_is_accepted(): void
    {
        [$first, $second] = $this->rooms(2);
        $reservation = $this->reservation($first);
        $reservation->update(['room_id' => $second->id]);

        $segment = $reservation->segments()->firstOrFail();

        $this->actingAs($this->admin())
            ->put(route('reservations.updateSegment', $segment), [
                'price_per_night' => $second->priceFor('YER'),
                'room_id'         => $second->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($second->id, $segment->fresh()->room_id);
    }
}
