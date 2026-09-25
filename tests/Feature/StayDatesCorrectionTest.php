<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تصحيح تاريخ/وقت الإقامة يجب أن يبقى بعد الحفظ.
 *
 * كان التجديد التلقائي يُعيد تمديد الإقامة في أول فتح للصفحة بعد التصحيح،
 * فيرى الموظف رسالة "تم التعديل بنجاح" والتاريخ القديم مكانه، ويُضاف مبلغ
 * لحساب النزيل دون أن يطلبه أحد.
 */
class StayDatesCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function stay(bool $autoRenew): Reservation
    {
        $admin = $this->admin();

        Shift::create([
            'user_id' => $admin->id, 'shift_date' => today(),
            'started_at' => now()->subHour(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);

        $guest = Guest::create([
            'full_name' => 'نزيل التصحيح', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '03' . random_int(1000000, 9999999),
        ]);

        return Reservation::create([
            'guest_id' => $guest->id,
            'room_id'  => Room::where('status', 'available')->firstOrFail()->id,
            'created_by' => $admin->id,
            'check_in_date' => today()->subDays(3), 'check_out_date' => today()->addDays(3),
            'check_in_time' => '14:00', 'check_out_time' => '13:00',
            'status' => 'checked_in', 'payment_status' => 'unpaid',
            'total_amount' => 120000, 'first_night_price' => 20000, 'renewal_price_per_night' => 20000,
            'auto_renew' => $autoRenew,
        ]);
    }

    private function correct(Reservation $reservation, string $checkOut)
    {
        return $this->actingAs($this->admin())->put(route('reservations.updateStayDates', $reservation), [
            'check_in_date'  => $reservation->check_in_date->toDateString(),
            'check_in_time'  => '14:00',
            'check_out_date' => $checkOut,
            'check_out_time' => '13:00',
        ]);
    }

    /** الحالة المُبلَّغ عنها: تقصير الإقامة ليوم مضى مع تجديد تلقائي مفعَّل. */
    public function test_a_shortened_stay_survives_opening_the_page_again(): void
    {
        $reservation = $this->stay(autoRenew: true);
        $target = today()->subDays(2)->toDateString();

        $this->correct($reservation, $target)->assertSessionHasNoErrors();
        $this->assertSame($target, $reservation->fresh()->check_out_date->toDateString());

        // فتح صفحة التفاصيل هو ما كان يُعيد التمديد
        $this->actingAs($this->admin())->get(route('reservations.show', $reservation))->assertOk();

        $this->assertSame($target, $reservation->fresh()->check_out_date->toDateString(),
            'تاريخ المغادرة المصحَّح يجب ألا يتغيّر بمجرد فتح الصفحة');
    }

    public function test_ending_the_stay_switches_auto_renew_off_and_says_so(): void
    {
        $reservation = $this->stay(autoRenew: true);

        $this->correct($reservation, today()->toDateString());

        $this->assertFalse((bool) $reservation->fresh()->auto_renew);
        $this->assertStringContainsString('أُوقف التجديد التلقائي', session('success'));
    }

    /** تقصير الإقامة لتاريخ مستقبلي لا يمسّ التجديد التلقائي — الإقامة مستمرة. */
    public function test_a_future_checkout_keeps_auto_renew_on(): void
    {
        $reservation = $this->stay(autoRenew: true);

        $this->correct($reservation, today()->addDay()->toDateString());

        $this->assertTrue((bool) $reservation->fresh()->auto_renew);
        $this->assertStringNotContainsString('أُوقف التجديد التلقائي', session('success'));
    }

    public function test_the_guest_is_not_billed_for_nights_nobody_asked_for(): void
    {
        $reservation = $this->stay(autoRenew: true);

        $this->correct($reservation, today()->subDays(2)->toDateString());
        $totalAfterCorrection = (float) $reservation->fresh()->total_amount;

        $this->actingAs($this->admin())->get(route('reservations.show', $reservation))->assertOk();

        $this->assertSame($totalAfterCorrection, (float) $reservation->fresh()->total_amount,
            'الإجمالي يجب ألا يزيد تلقائياً بعد تصحيح ينهي الإقامة');
    }

    /** حذف تجديد يُنهي الإقامة يوقف التجديد التلقائي كذلك — نفس المسار. */
    public function test_deleting_a_renewal_that_ends_the_stay_also_stops_auto_renew(): void
    {
        $reservation = $this->stay(autoRenew: false);

        $this->actingAs($this->admin())->post(route('reservations.renew', $reservation), [
            'new_check_out_date' => today()->addDays(5)->toDateString(),
            'renewal_price' => 20000, 'advance_payment' => 0, 'payment_method' => 'cash',
        ]);

        $reservation->update(['check_out_date' => today()->addDays(5), 'auto_renew' => true]);
        $segment = $reservation->segments()->where('type', 'renewal')->firstOrFail();
        // فترة تجديد طويلة بحيث يعود تاريخ الخروج لما قبل اليوم بعد حذفها
        $segment->update(['nights' => 6]);

        $this->actingAs($this->admin())->delete(route('reservations.deleteSegment', $segment));

        $fresh = $reservation->fresh();
        $this->assertTrue($fresh->check_out_date->lte(today()));
        $this->assertFalse((bool) $fresh->auto_renew);
    }

    public function test_times_only_corrections_are_saved(): void
    {
        $reservation = $this->stay(autoRenew: false);

        $this->actingAs($this->admin())->put(route('reservations.updateStayDates', $reservation), [
            'check_in_date'  => $reservation->check_in_date->toDateString(),
            'check_in_time'  => '09:30',
            'check_out_date' => $reservation->check_out_date->toDateString(),
            'check_out_time' => '11:45',
        ])->assertSessionHasNoErrors();

        $fresh = $reservation->fresh();
        $this->assertSame('09:30', substr($fresh->check_in_time, 0, 5));
        $this->assertSame('11:45', substr($fresh->check_out_time, 0, 5));
    }
}
