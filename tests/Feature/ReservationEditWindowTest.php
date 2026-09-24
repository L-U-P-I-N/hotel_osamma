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

/**
 * مهلة تعديل الحجز: الموظف يعدّل ما سجّله ما دامت وردية الحجز مفتوحة؛ وبعد
 * إقفالها تُصفَّى أرقامها فيُمنع التعديل. المدير غير مقيَّد — وهو ما يتيح له
 * إلغاء تجديد أُدخل خطأً بتصحيح تاريخ المغادرة.
 */
class ReservationEditWindowTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function receptionist(): User
    {
        $user = User::role('receptionist')->firstOrFail();

        foreach (['reservation.edit', 'payments.create', 'reservation.discount', 'checkin.view', 'reservation.renew'] as $key) {
            PermissionService::toggle($user, $key, true, $this->admin());
        }

        return $user;
    }

    private function openShift(User $user): Shift
    {
        return Shift::create([
            'user_id' => $user->id, 'shift_date' => today(),
            'started_at' => now()->subHour(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);
    }

    /** حجز نشط مع تجديد مسجَّل في وردية الموظف. */
    private function stayWithRenewal(User $actor): Reservation
    {
        $shift = $this->openShift($actor);

        $guest = Guest::create([
            'full_name' => 'نزيل المهلة', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '0177' . random_int(10000, 99999),
        ]);

        $reservation = Reservation::create([
            'guest_id' => $guest->id,
            'room_id'  => Room::where('status', 'available')->firstOrFail()->id,
            'created_by' => $actor->id,
            'check_in_date' => today()->subDay(), 'check_out_date' => today()->addDay(),
            'check_in_time' => '14:00', 'check_out_time' => '13:00',
            'status' => 'checked_in', 'payment_status' => 'unpaid',
            'total_amount' => 40000, 'first_night_price' => 20000, 'renewal_price_per_night' => 20000,
        ]);

        $this->actingAs($actor)->post(route('reservations.renew', $reservation), [
            'new_check_out_date' => today()->addDays(3)->toDateString(),
            'renewal_price'      => 20000,
            'advance_payment'    => 0,
            'payment_method'     => 'cash',
        ]);

        return $reservation->refresh();
    }

    private function closeAllShifts(): void
    {
        Shift::query()->update(['is_closed' => true, 'ended_at' => now()]);
    }

    private function correctCheckout(User $actor, Reservation $reservation, string $checkOut)
    {
        return $this->actingAs($actor)->put(route('reservations.updateStayDates', $reservation), [
            'check_in_date'  => $reservation->check_in_date->toDateString(),
            'check_in_time'  => '14:00',
            'check_out_date' => $checkOut,
            'check_out_time' => '13:00',
        ]);
    }

    public function test_staff_can_still_edit_while_their_shift_is_open(): void
    {
        $user = $this->receptionist();
        $reservation = $this->stayWithRenewal($user);

        $this->assertFalse($reservation->isEditLockedFor($user));

        $this->correctCheckout($user, $reservation, today()->addDays(2)->toDateString())
            ->assertSessionHasNoErrors();
    }

    public function test_staff_lose_the_edit_window_once_the_shift_is_closed(): void
    {
        $user = $this->receptionist();
        $reservation = $this->stayWithRenewal($user);
        $this->closeAllShifts();

        $this->assertTrue($reservation->isEditLockedFor($user));

        $this->correctCheckout($user, $reservation, today()->addDays(2)->toDateString())
            ->assertSessionHasErrors('error');

        $this->assertStringContainsString('انتهت مهلة تعديل هذا الحجز', session('errors')->get('error')[0]);
    }

    public function test_the_closed_shift_blocks_every_editing_action_for_staff(): void
    {
        $user = $this->receptionist();
        $reservation = $this->stayWithRenewal($user);
        $this->closeAllShifts();

        $this->actingAs($user)->post(route('reservations.applyDiscount', $reservation), [
            'discount_type' => 'fixed', 'discount_value' => 5000,
        ])->assertSessionHasErrors('error');

        $this->actingAs($user)->patch(route('reservations.updateCheckInDate', $reservation), [
            'check_in_date' => today()->toDateString(),
        ])->assertSessionHasErrors('error');

        $this->actingAs($user)->post(route('reservations.recomputeSegments', $reservation))
            ->assertSessionHasErrors('error');

        // لم يتغيّر شيء من أرقام الحجز
        $this->assertSame(80000.0, (float) $reservation->fresh()->total_amount);
    }

    /** العمل اليومي يبقى متاحاً بعد إقفال الوردية — ليس تصحيحاً لعملٍ منتهٍ. */
    public function test_daily_operations_stay_available_after_the_shift_closes(): void
    {
        $user = $this->receptionist();
        $reservation = $this->stayWithRenewal($user);
        $this->closeAllShifts();
        $this->openShift($user);   // ورديته الجديدة اليوم

        $this->actingAs($user)->post(route('payments.store'), [
            'reservation_id' => $reservation->id, 'amount' => 10000, 'method' => 'cash',
        ])->assertSessionHasNoErrors();

        $this->assertSame(10000.0, (float) $reservation->fresh()->paid_amount);
    }

    public function test_the_admin_is_never_locked_out(): void
    {
        $user = $this->receptionist();
        $reservation = $this->stayWithRenewal($user);
        $this->closeAllShifts();

        $this->assertFalse($reservation->isEditLockedFor($this->admin()));
    }

    /**
     * جوهر المشكلة المُبلَّغ عنها: تجديد أُدخل خطأً يُلغى بتصحيح تاريخ
     * المغادرة — كان يُرفض حتى على المدير لأن فترة التجديد تخصّ وردية مقفلة.
     */
    public function test_admin_cancels_a_wrong_renewal_by_correcting_the_checkout_date(): void
    {
        $user = $this->receptionist();
        $reservation = $this->stayWithRenewal($user);
        $this->closeAllShifts();

        $this->assertSame(1, $reservation->segments()->where('type', 'renewal')->count());
        $this->assertSame(80000.0, (float) $reservation->total_amount);

        $this->correctCheckout($this->admin(), $reservation, today()->addDay()->toDateString())
            ->assertSessionHasNoErrors();

        $reservation->refresh();

        $this->assertSame(0, $reservation->segments()->where('type', 'renewal')->count());
        $this->assertSame(40000.0, (float) $reservation->total_amount);
        $this->assertSame(today()->addDay()->toDateString(), $reservation->check_out_date->toDateString());
    }

    public function test_admin_can_still_delete_the_renewal_segment_directly(): void
    {
        $user = $this->receptionist();
        $reservation = $this->stayWithRenewal($user);
        $this->closeAllShifts();

        $segment = $reservation->segments()->where('type', 'renewal')->firstOrFail();

        $this->actingAs($this->admin())
            ->delete(route('reservations.deleteSegment', $segment))
            ->assertSessionHasNoErrors();

        $this->assertSame(40000.0, (float) $reservation->fresh()->total_amount);
    }

    /** الشاشة توضّح انتهاء المهلة بدل أزرارٍ تقود لرسالة رفض. */
    public function test_the_page_explains_the_closed_window_instead_of_offering_the_buttons(): void
    {
        $user = $this->receptionist();
        $reservation = $this->stayWithRenewal($user);

        $this->actingAs($user)->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('تصحيح: تعديل تاريخ ووقت الوصول/المغادرة');

        $this->closeAllShifts();

        $locked = $this->actingAs($user)->get(route('reservations.show', $reservation))->assertOk();
        $locked->assertSee('انتهت المهلة');
        $locked->assertDontSee('تصحيح: تعديل تاريخ ووقت الوصول/المغادرة');

        // المدير يرى الأزرار كاملةً
        $this->actingAs($this->admin())->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('تصحيح: تعديل تاريخ ووقت الوصول/المغادرة');
    }
}
