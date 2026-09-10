<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تعديل سعر حجز عبر شاشة "تعديل الحجز" يجب أن يُطبَّق فعلياً، حتى إن كانت
 * فترات المحاسبة الحالية متطابقة داخلياً مع الإجمالي القديم (preserveHistory)
 * — سيناريو حقيقي: نزيل اتّفق مع موظف على سعر عند تسجيل الدخول، ثم صحّح موظف
 * آخر السعر لاحقاً بعد أن دفع النزيل مبلغاً مختلفاً، فكان التعديل يُحفَظ
 * "بنجاح" ظاهرياً بينما يبقى السعر المعروض والمحتسَب كما كان.
 */
class ReservationPriceEditTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    public function test_correcting_the_price_of_a_reconciled_reservation_actually_changes_the_total(): void
    {
        $admin = $this->admin();
        $room = Room::with('roomType')->where('status', 'available')->firstOrFail();
        $room->roomType->update(['min_price' => 5000, 'base_price' => 20000, 'max_price' => 50000]);
        $room->update(['price_yer' => 20000]);

        $checkIn = today();
        $checkOut = today()->addDays(2);

        $reservation = Reservation::create([
            'guest_id'       => Guest::create(['full_name' => 'نزيل اختبار السعر', 'nationality' => 'يمني', 'id_type' => 'national_id'])->id,
            'room_id'        => $room->id,
            'created_by'     => $admin->id,
            'check_in_date'  => $checkIn,
            'check_out_date' => $checkOut,
            'check_in_time'  => '14:00',
            'check_out_time' => '13:00',
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => 40000, // 20,000 × 2 ليالٍ — تمثيل الاتفاق الأول مع موظف محمد
        ]);

        // الفترات تتطابق مع الإجمالي الحالي (لا تضارب) — هذا بالضبط الشرط الذي
        // كان يُسقِط السعر الجديد صامتاً قبل الإصلاح
        app(\App\Services\ReservationSegmentService::class)->recordInitial($reservation, 20000, 20000, 2, $admin->id);
        $this->assertTrue(app(\App\Services\ReservationSegmentService::class)->reconciles($reservation));

        // موظف أسامة يصحّح السعر إلى 25,000 لكل ليلة (نفس التواريخ، لا تجديد)
        $response = $this->actingAs($admin)->put("/reservations/{$reservation->id}", [
            'check_in_date'    => $checkIn->toDateString(),
            'check_out_date'   => $checkOut->toDateString(),
            'guest_full_name'  => $reservation->guest->full_name,
            'guest_id_type'    => 'national_id',
            'price_per_night'  => 25000,
        ]);

        $response->assertRedirect(route('reservations.show', $reservation))->assertSessionHasNoErrors();

        $reservation->refresh();
        $this->assertEqualsWithDelta(50000, (float) $reservation->total_amount, 1,
            'يجب أن يعكس الإجمالي السعر الجديد 25,000 × 2 لا 20,000 القديم');

        $segment = $reservation->segments()->firstOrFail();
        $this->assertEqualsWithDelta(25000, (float) $segment->price_per_night, 0.01,
            'الفترة المعروضة يجب أن تعكس السعر الجديد أيضاً لا القديم');
    }

    /** بلا سعر جديد صريح (نفس السعر أو حقل فارغ) تبقى الفترات كما هي كالمعتاد */
    public function test_resubmitting_the_same_price_still_preserves_segment_history(): void
    {
        $admin = $this->admin();
        $room = Room::with('roomType')->where('status', 'available')->firstOrFail();
        $room->roomType->update(['min_price' => 5000, 'base_price' => 20000, 'max_price' => 50000]);
        $room->update(['price_yer' => 20000]);
        $checkIn = today();
        $checkOut = today()->addDays(2);

        $reservation = Reservation::create([
            'guest_id'       => Guest::create(['full_name' => 'نزيل اختبار السعر 2', 'nationality' => 'يمني', 'id_type' => 'national_id'])->id,
            'room_id'        => $room->id,
            'created_by'     => $admin->id,
            'check_in_date'  => $checkIn,
            'check_out_date' => $checkOut,
            'check_in_time'  => '14:00',
            'check_out_time' => '13:00',
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => 40000,
        ]);
        app(\App\Services\ReservationSegmentService::class)->recordInitial($reservation, 20000, 20000, 2, $admin->id);
        $segmentIdBefore = $reservation->segments()->firstOrFail()->id;

        $this->actingAs($admin)->put("/reservations/{$reservation->id}", [
            'check_in_date'   => $checkIn->toDateString(),
            'check_out_date'  => $checkOut->toDateString(),
            'guest_full_name' => $reservation->guest->full_name,
            'guest_id_type'   => 'national_id',
            'price_per_night' => 20000,
        ])->assertSessionHasNoErrors();

        $reservation->refresh();
        $this->assertEqualsWithDelta(40000, (float) $reservation->total_amount, 1);
        $this->assertSame($segmentIdBefore, $reservation->segments()->firstOrFail()->id,
            'بلا تغيير فعلي بالسعر يجب ألا تُعاد بناء الفترات');
    }
}
