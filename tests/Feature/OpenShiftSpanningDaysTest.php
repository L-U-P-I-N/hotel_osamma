<?php

namespace Tests\Feature;

use App\Models\CashWithdrawal;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * وردية نُسي إقفالها فامتدّت أكثر من يوم.
 *
 * الموظف يسجّل اليوم على وردية الأمس، فيجد عند الإقفال يومين مخلوطين. القدرة
 * على فصلهما كانت موجودة («إقفال المحدد كوردية بتاريخ سابق») لكن جدول
 * المستلمات كان يعرض الوقت بلا تاريخ — فلا يُميَّز أمسُ من اليوم ولا يُعرف ما
 * يُحدَّد. فصارت الأيام تُعرض مفصولة ويُحدَّد اليوم بضغطة.
 */
class OpenShiftSpanningDaysTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::role('admin')->firstOrFail();
    }

    private function openShift(): Shift
    {
        return Shift::create([
            'user_id' => $this->admin()->id, 'shift_date' => today()->subDay(),
            'started_at' => now()->subDay(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);
    }

    private function payment(Shift $shift, $date, float $amount): Payment
    {
        $room  = Room::where('room_sub_type', 'regular')->orderBy('room_number')->firstOrFail();
        $guest = Guest::create([
            'full_name' => 'نزيل ' . $amount, 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '04' . random_int(1000000, 9999999),
        ]);
        $reservation = Reservation::create([
            'guest_id' => $guest->id, 'room_id' => $room->id, 'created_by' => $this->admin()->id,
            'check_in_date' => $date, 'check_out_date' => today()->addDay(),
            'status' => 'checked_in', 'payment_status' => 'unpaid', 'total_amount' => $amount,
        ]);

        return Payment::create([
            'reservation_id' => $reservation->id, 'shift_id' => $shift->id,
            'amount' => $amount, 'currency' => 'YER', 'method' => 'cash',
            'payment_date' => $date, 'received_by' => $this->admin()->id,
        ]);
    }

    private function withdrawal(Shift $shift, $date, float $amount): CashWithdrawal
    {
        return CashWithdrawal::create([
            'shift_id' => $shift->id, 'funding_source' => 'shift',
            'amount' => $amount, 'currency' => 'YER', 'withdrawal_date' => $date,
            'withdrawn_by_name' => 'مورّد', 'handed_by_name' => $this->admin()->name,
            'withdrawal_type' => 'expense',
        ]);
    }

    private function days(): \Illuminate\Support\Collection
    {
        return $this->actingAs($this->admin())->get(route('shifts.index'))->assertOk()->viewData('shiftDays');
    }

    public function test_the_days_are_grouped_with_their_record_ids(): void
    {
        $shift = $this->openShift();
        $yesterdayPayment = $this->payment($shift, today()->subDay(), 20000);
        $yesterdayOut     = $this->withdrawal($shift, today()->subDay(), 3000);
        $todayPayment     = $this->payment($shift, today(), 50000);

        $days = $this->days();

        $this->assertCount(2, $days, 'لم تُفصل أيام الوردية');

        $yesterday = $days->firstWhere('date', today()->subDay()->toDateString());
        $this->assertSame([$yesterdayPayment->id], $yesterday['payments']);
        $this->assertSame([$yesterdayOut->id], $yesterday['withdrawals']);
        $this->assertSame(20000.0, $yesterday['received']);
        $this->assertSame(3000.0, $yesterday['out']);
        $this->assertFalse($yesterday['is_today']);

        $today = $days->firstWhere('date', today()->toDateString());
        $this->assertSame([$todayPayment->id], $today['payments']);
        $this->assertTrue($today['is_today']);
    }

    /** وردية ليوم واحد لا تُظهر اللافتة ولا تُعقّد الشاشة */
    public function test_a_single_day_shift_has_one_group(): void
    {
        $shift = $this->openShift();
        $this->payment($shift, today(), 10000);
        $this->payment($shift, today(), 15000);

        $this->assertCount(1, $this->days());
    }

    /** التاريخ يظهر في صف المستلمة حين تمتدّ الوردية — وهو ما كان ناقصاً */
    public function test_the_payment_row_shows_the_date_when_the_shift_spans_days(): void
    {
        $shift = $this->openShift();
        $this->payment($shift, today()->subDay(), 20000);
        $this->payment($shift, today(), 50000);

        $this->actingAs($this->admin())
            ->get(route('shifts.index'))
            ->assertOk()
            ->assertSee(today()->subDay()->format('d/m/Y') . ' ', false);
    }

    /** والفصل يُنشئ وردية بتاريخ الأمس تحمل حركاته وحدها */
    public function test_closing_the_earlier_day_moves_only_its_records(): void
    {
        $shift = $this->openShift();
        $yesterdayPayment = $this->payment($shift, today()->subDay(), 20000);
        $yesterdayOut     = $this->withdrawal($shift, today()->subDay(), 3000);
        $todayPayment     = $this->payment($shift, today(), 50000);

        $this->actingAs($this->admin())
            ->post(route('shifts.closePast'), [
                'shift_date'     => today()->subDay()->toDateString(),
                'payment_ids'    => [$yesterdayPayment->id],
                'withdrawal_ids' => [$yesterdayOut->id],
                'actual_amount'  => 17000,
            ])
            ->assertSessionHasNoErrors();

        // الأحدث: البذرة تحمل ورديات مقفلة بتواريخ سابقة أيضاً
        $past = Shift::where('shift_date', today()->subDay())->where('is_closed', true)
            ->latest('id')->firstOrFail();

        $this->assertSame($past->id, $yesterdayPayment->fresh()->shift_id);
        $this->assertSame($past->id, $yesterdayOut->fresh()->shift_id);
        // وحركة اليوم تبقى في الوردية المفتوحة
        $this->assertSame($shift->id, $todayPayment->fresh()->shift_id);
    }
}
