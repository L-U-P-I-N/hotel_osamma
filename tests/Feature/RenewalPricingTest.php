<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenewalPricingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function openShift(User $user): Shift
    {
        return Shift::create([
            'user_id' => $user->id, 'shift_date' => today(),
            'started_at' => now()->subHour(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);
    }

    /** نزيل سجّل الدخول بـ35 ألف/ليلة ضمن نطاق التسعير */
    private function checkedInGuest(float $nightly = 35000): Reservation
    {
        $room = Room::with('roomType')->where('status', 'available')->firstOrFail();
        $room->roomType->update(['min_price' => 30000, 'base_price' => 35000, 'max_price' => 40000]);
        $room->update(['price_yer' => $nightly]);

        return Reservation::create([
            'guest_id'       => Guest::firstOrFail()->id,
            'room_id'        => $room->id,
            'created_by'     => $this->admin()->id,
            'check_in_date'  => today(),
            'check_out_date' => today()->addDays(2),
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => $nightly * 2,
            'paid_amount'    => 0,
        ]);
    }

    private function renew(Reservation $r, array $data)
    {
        return $this->actingAs($this->admin())
            ->post("/reservations/{$r->id}/renew", array_merge([
                'new_check_out_date' => $r->check_out_date->copy()->addDays(2)->toDateString(),
            ], $data));
    }

    /** جوهر الطلب: سعر التجديد مفتوح ولا يخضع لنطاق إعدادات التسعير */
    public function test_renewal_price_is_not_bound_by_the_pricing_settings(): void
    {
        $r = $this->checkedInGuest();
        $this->openShift($this->admin());

        // 90,000 خارج نطاق النوع (30,000–40,000) — يجب أن يُقبل في التجديد
        $this->renew($r, ['renewal_price_per_night' => 90000])->assertSessionHasNoErrors();

        $r->refresh();
        $this->assertEqualsWithDelta(90000, (float) $r->renewal_price_per_night, 0.01);
        // 70,000 (ليلتان أصليتان) + 180,000 (ليلتا تجديد) = 250,000
        $this->assertEqualsWithDelta(250000, (float) $r->total_amount, 1);
    }

    /** خصم بمبلغ ثابت على التجديد وحده */
    public function test_fixed_discount_applies_to_the_renewal_only(): void
    {
        $r = $this->checkedInGuest();
        $this->openShift($this->admin());

        $this->renew($r, [
            'renewal_price_per_night' => 50000,   // ليلتان = 100,000
            'renewal_discount_type'   => 'fixed',
            'renewal_discount_value'  => 20000,
        ])->assertSessionHasNoErrors();

        $r->refresh();
        // 70,000 + (100,000 - 20,000) = 150,000
        $this->assertEqualsWithDelta(150000, (float) $r->total_amount, 1);

        $segment = $r->segments()->where('type', 'renewal')->latest('id')->firstOrFail();
        $this->assertEqualsWithDelta(80000, (float) $segment->amount, 1, 'الفترة تُسجَّل بالصافي بعد الخصم');
    }

    public function test_percent_discount_applies_to_the_renewal_only(): void
    {
        $r = $this->checkedInGuest();
        $this->openShift($this->admin());

        $this->renew($r, [
            'renewal_price_per_night' => 50000,   // ليلتان = 100,000
            'renewal_discount_type'   => 'percent',
            'renewal_discount_value'  => 25,
        ])->assertSessionHasNoErrors();

        // 70,000 + (100,000 - 25,000) = 145,000
        $this->assertEqualsWithDelta(145000, (float) $r->fresh()->total_amount, 1);
    }

    public function test_partial_payment_on_renewal(): void
    {
        $r = $this->checkedInGuest();
        $this->openShift($this->admin());

        $this->renew($r, [
            'renewal_price_per_night' => 40000,  // ليلتان = 80,000 ; الإجمالي 150,000
            'advance_payment'         => 50000,
            'payment_method'          => 'cash',
        ])->assertSessionHasNoErrors();

        $r->refresh();
        $this->assertEqualsWithDelta(150000, (float) $r->total_amount, 1);
        $this->assertEqualsWithDelta(50000, (float) $r->paid_amount, 1);
        $this->assertSame('partial', $r->payment_status);
        $this->assertEqualsWithDelta(100000, $r->balance, 1);
    }

    public function test_full_payment_on_renewal_marks_it_paid(): void
    {
        $r = $this->checkedInGuest();
        $this->openShift($this->admin());

        $this->renew($r, [
            'renewal_price_per_night' => 40000,
            'advance_payment'         => 150000, // كامل المطلوب
        ])->assertSessionHasNoErrors();

        $r->refresh();
        $this->assertEqualsWithDelta(150000, (float) $r->paid_amount, 1);
        $this->assertSame('paid', $r->payment_status);
        $this->assertEqualsWithDelta(0, $r->balance, 1);
    }

    /** الدفع لا يتجاوز المطلوب مهما أُدخل */
    public function test_overpayment_is_capped_at_the_balance(): void
    {
        $r = $this->checkedInGuest();
        $this->openShift($this->admin());

        $this->renew($r, [
            'renewal_price_per_night' => 40000,
            'advance_payment'         => 900000,
        ])->assertSessionHasNoErrors();

        $r->refresh();
        $this->assertEqualsWithDelta(150000, (float) $r->paid_amount, 1, 'لا يُسجَّل أكثر من المستحق');
        $this->assertEqualsWithDelta(0, $r->balance, 1);
    }

    /** الخصم مع الدفع الكامل معاً */
    public function test_discount_and_full_payment_together(): void
    {
        $r = $this->checkedInGuest();
        $this->openShift($this->admin());

        $this->renew($r, [
            'renewal_price_per_night' => 50000,
            'renewal_discount_type'   => 'fixed',
            'renewal_discount_value'  => 30000,
            'advance_payment'         => 140000, // 70,000 + 70,000
        ])->assertSessionHasNoErrors();

        $r->refresh();
        $this->assertEqualsWithDelta(140000, (float) $r->total_amount, 1);
        $this->assertEqualsWithDelta(140000, (float) $r->paid_amount, 1);
        $this->assertSame('paid', $r->payment_status);
    }
}
