<?php
namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\GuestCredit;
use App\Models\Guest;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use App\Services\CheckOutService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * المغادرة المبكرة والمبالغ المتبقية للنزلاء.
 *
 * نزيل حجز ليلتين ودفع قيمتهما ثم أقام ليلة واحدة: كان الفرق يضيع بلا سجل.
 * الآن يهبط المستحق إلى الليلة المُقامة، ويُرحَّل الفرق رصيداً باسم النزيل يظهر
 * في قسم مستقل حتى يُصرف له أو يتنازل عنه — والنقد لا يتحرك إلا عند الصرف.
 */
class GuestCreditEarlyDepartureTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    /**
     * الوقت مثبّت: احتساب الليالي يعتمد على حد الساعة 1 ظهراً، فلو جرى الاختبار
     * بعد الظهر لعُدّ يومُ المغادرة ليلةً مستهلكة واختلفت النتيجة بحسب ساعة
     * التشغيل. التثبيت قبل الظهر يجعل السيناريو واحداً دائماً.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function shift(User $user): Shift
    {
        return Shift::firstOrCreate(
            ['user_id' => $user->id, 'shift_date' => today(), 'is_closed' => false],
            ['started_at' => now()->subHour(), 'opening_balance_yer' => 0]
        );
    }

    /** إقامة ليلتين مدفوعة بالكامل، والنزيل يغادر بعد ليلة واحدة. */
    private function paidTwoNightStay(): Reservation
    {
        $admin = $this->admin();
        $this->shift($admin);

        $guest = Guest::create([
            'full_name' => 'نزيل غادر مبكراً', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '04' . random_int(1000000, 9999999),
        ]);

        $reservation = Reservation::create([
            'guest_id'   => $guest->id,
            'room_id'    => Room::where('status', 'available')->firstOrFail()->id,
            'created_by' => $admin->id,
            // دخل أمس بعد حد الساعة 1 ظهراً ⇒ ليلتان حتى خروج الغد 1 ظهراً
            'check_in_date' => today()->subDay(), 'check_in_time' => '14:00',
            'check_out_date' => today()->addDay(), 'check_out_time' => '13:00',
            'status' => 'checked_in', 'payment_status' => 'paid',
            'total_amount' => 40000, 'paid_amount' => 40000,
            'first_night_price' => 20000, 'renewal_price_per_night' => 20000,
        ]);

        Payment::create([
            'reservation_id' => $reservation->id,
            'received_by'    => $admin->id,
            'amount'         => 40000,
            'currency'       => 'YER',
            'method'         => 'cash',
            'payment_date'   => now()->subDay(),
            'type'           => 'reservation',
        ]);

        return $reservation->refresh();
    }

    /* ═══ العرض المقترح قبل التنفيذ ═══ */

    public function test_the_quote_prices_only_the_nights_actually_stayed(): void
    {
        $quote = app(CheckOutService::class)->earlyDepartureQuote($this->paidTwoNightStay());

        $this->assertNotNull($quote);
        $this->assertSame(2, $quote['planned_nights']);
        $this->assertSame(1, $quote['actual_nights']);
        $this->assertSame(1, $quote['unused_nights']);
        $this->assertEquals(20000, $quote['savings']);
        $this->assertEquals(20000, $quote['new_total']);
        $this->assertEquals(20000, $quote['refundable']);
        $this->assertEquals(0, $quote['new_balance']);
    }

    public function test_no_quote_when_the_guest_leaves_on_time(): void
    {
        $reservation = $this->paidTwoNightStay();
        $reservation->update(['check_out_date' => today(), 'check_out_time' => '13:00']);

        $this->assertNull(app(CheckOutService::class)->earlyDepartureQuote($reservation->refresh()));
    }

    public function test_the_checkout_screen_offers_the_settlement(): void
    {
        $reservation = $this->paidTwoNightStay();

        $this->actingAs($this->admin())
            ->get(route('checkout.show', $reservation))
            ->assertOk()
            ->assertSee('مغادرة مبكرة', false)
            ->assertSee('مبلغ متبقٍّ للنزيل', false)
            ->assertSee('ترحيله إلى «المبالغ المتبقية للنزلاء»', false);
    }

    /* ═══ الترحيل رصيداً ═══ */

    public function test_carrying_the_credit_lets_the_checkout_finish_with_a_zero_balance(): void
    {
        $reservation = $this->paidTwoNightStay();

        $this->actingAs($this->admin())
            ->post(route('checkout.process', $reservation), [
                'early_departure' => 1,
                'credit_action'   => 'carry',
                'checkout_notes'  => 'غادر مبكراً لسفر مفاجئ',
            ])
            ->assertRedirect(route('checkout.done', $reservation->id));

        $reservation->refresh();

        $this->assertSame('checked_out', $reservation->status);
        $this->assertEquals(20000, $reservation->total_amount);
        $this->assertEquals(20000, $reservation->paid_amount);
        $this->assertEquals(0, $reservation->balance);
        $this->assertSame('paid', $reservation->payment_status);
        $this->assertSame('غادر مبكراً لسفر مفاجئ', $reservation->checkout_notes);

        $credit = GuestCredit::where('reservation_id', $reservation->id)->firstOrFail();
        $this->assertSame(GuestCredit::STATUS_OPEN, $credit->status);
        $this->assertEquals(20000, $credit->amount);
        $this->assertSame($reservation->guest_id, $credit->guest_id);
    }

    /** إجمالي الغرفة قبل الخصم لا يتغيّر، فتبقى فترات الغرفة مطابقة له. */
    public function test_the_room_periods_still_reconcile_after_the_settlement(): void
    {
        $reservation = $this->paidTwoNightStay();
        app(\App\Services\ReservationSegmentService::class)
            ->recordInitial($reservation, 20000, 20000, 2, $this->admin()->id);

        $grossBefore = $reservation->gross_total;

        $this->actingAs($this->admin())
            ->post(route('checkout.process', $reservation), ['early_departure' => 1, 'credit_action' => 'carry']);

        $reservation->refresh();

        $this->assertEquals($grossBefore, $reservation->gross_total);
        $this->assertTrue(app(\App\Services\ReservationSegmentService::class)->reconciles($reservation));
    }

    /**
     * المبلغ ينتقل من الإيراد إلى التزام على الفندق — والنقد يبقى في الصندوق.
     * الطرف المدين حساب مسموحات مقابل (4195) لا إيراد الغرف نفسه: الإيراد
     * الإجمالي يبقى سليماً ويظهر الردّ سطراً مستقلاً في قائمة الدخل.
     */
    public function test_the_journal_moves_the_amount_from_revenue_to_a_liability(): void
    {
        $reservation = $this->paidTwoNightStay();

        $this->actingAs($this->admin())
            ->post(route('checkout.process', $reservation), ['early_departure' => 1, 'credit_action' => 'carry']);

        $credit = GuestCredit::where('reservation_id', $reservation->id)->firstOrFail();

        $allowance = ChartOfAccount::where('code', '4195')->firstOrFail();
        $liability = ChartOfAccount::where('code', '2230')->firstOrFail();

        $lines = JournalLine::whereHas('journalEntry', fn ($q) => $q
            ->where('source_type', GuestCredit::class)->where('source_id', $credit->id))->get();

        $this->assertEquals(20000, (float) $lines->where('account_code', $allowance->code)->sum('debit'));
        $this->assertEquals(20000, (float) $lines->where('account_code', $liability->code)->sum('credit'));
        // إيراد الغرف نفسه لم يُمَس
        $this->assertSame(0, $lines->where('account_code', '4110')->count());

        // لا استرجاع نقدي: المبلغ لم يخرج من الصندوق بعد
        $this->assertSame(0, Refund::where('reservation_id', $reservation->id)->count());
    }

    /* ═══ الصرف الفوري عند الخروج ═══ */

    public function test_paying_the_credit_out_at_checkout_takes_the_cash_out_of_the_shift(): void
    {
        $reservation = $this->paidTwoNightStay();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('checkout.process', $reservation), [
                'early_departure' => 1,
                'credit_action'   => 'payout',
                'credit_method'   => 'cash',
            ]);

        $credit = GuestCredit::where('reservation_id', $reservation->id)->firstOrFail();
        $this->assertSame(GuestCredit::STATUS_SETTLED, $credit->status);
        $this->assertNotNull($credit->refund_id);

        $refund = Refund::findOrFail($credit->refund_id);
        $this->assertEquals(20000, $refund->amount);
        $this->assertFalse((bool) $refund->affects_paid_amount);
        $this->assertSame($this->shift($admin)->id, $refund->shift_id);

        // خصم الرصيد مرة واحدة فقط: المدفوع يبقى مساوياً للمستحق الجديد
        $this->assertEquals(20000, $reservation->refresh()->paid_amount);
        $this->assertEquals(20000, $this->shift($admin)->refresh()->total_refunds_yer);
    }

    /* ═══ إدارة الأرصدة من صفحتها ═══ */

    public function test_the_credits_page_lists_and_settles_the_balance(): void
    {
        $reservation = $this->paidTwoNightStay();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('checkout.process', $reservation), ['early_departure' => 1, 'credit_action' => 'carry']);

        $credit = GuestCredit::where('reservation_id', $reservation->id)->firstOrFail();

        $this->actingAs($admin)->get(route('guest-credits.index'))
            ->assertOk()
            ->assertSee('المبالغ المتبقية للنزلاء', false)
            ->assertSee('نزيل غادر مبكراً', false)
            ->assertSee('20,000');

        $this->actingAs($admin)
            ->post(route('guest-credits.settle', $credit), ['settlement_method' => 'cash'])
            ->assertRedirect();

        $this->assertSame(GuestCredit::STATUS_SETTLED, $credit->refresh()->status);
        $this->assertEquals(20000, Refund::findOrFail($credit->refund_id)->amount);
    }

    public function test_a_settled_credit_cannot_be_settled_again(): void
    {
        $reservation = $this->paidTwoNightStay();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('checkout.process', $reservation), ['early_departure' => 1, 'credit_action' => 'payout']);

        $credit = GuestCredit::where('reservation_id', $reservation->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('guest-credits.settle', $credit), ['settlement_method' => 'cash'])
            ->assertSessionHas('error');

        $this->assertSame(1, Refund::where('reservation_id', $reservation->id)->count());
    }

    public function test_cancelling_the_credit_returns_the_amount_to_revenue(): void
    {
        $reservation = $this->paidTwoNightStay();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('checkout.process', $reservation), ['early_departure' => 1, 'credit_action' => 'carry']);

        $credit = GuestCredit::where('reservation_id', $reservation->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('guest-credits.cancel', $credit), ['reason' => 'تنازل النزيل عن المبلغ'])
            ->assertRedirect();

        $this->assertSame(GuestCredit::STATUS_CANCELLED, $credit->refresh()->status);
        // المبلغ عاد إيراداً فعاد "المدفوع" لقيمته الكاملة
        $this->assertEquals(40000, $reservation->refresh()->paid_amount);
        $this->assertSame(0, Refund::where('reservation_id', $reservation->id)->count());
    }

    public function test_cancelling_needs_a_documented_reason(): void
    {
        $reservation = $this->paidTwoNightStay();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('checkout.process', $reservation), ['early_departure' => 1, 'credit_action' => 'carry']);

        $credit = GuestCredit::where('reservation_id', $reservation->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('guest-credits.cancel', $credit), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(GuestCredit::STATUS_OPEN, $credit->refresh()->status);
    }

    /* ═══ الحالة المقابلة: نزيل لم يدفع كل شيء ═══ */

    public function test_an_under_paid_guest_is_not_billed_for_the_night_he_skipped(): void
    {
        $reservation = $this->paidTwoNightStay();
        // دفع ليلة واحدة فقط من الليلتين
        $reservation->payments()->first()->update(['amount' => 20000]);
        $reservation->refresh()->recalculatePaidAmount();

        $this->assertEquals(20000, $reservation->refresh()->balance);

        $this->actingAs($this->admin())
            ->post(route('checkout.process', $reservation), ['early_departure' => 1])
            ->assertRedirect(route('checkout.done', $reservation->id));

        $reservation->refresh();

        $this->assertSame('checked_out', $reservation->status);
        $this->assertEquals(20000, $reservation->total_amount);
        $this->assertEquals(0, $reservation->balance);
        // لا رصيد مستحق للنزيل: دفع بالضبط ما أقام
        $this->assertSame(0, GuestCredit::where('reservation_id', $reservation->id)->count());
    }

    /* ═══ الصلاحيات ═══ */

    public function test_a_receptionist_sees_the_page_but_cannot_settle(): void
    {
        $reservation = $this->paidTwoNightStay();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('checkout.process', $reservation), ['early_departure' => 1, 'credit_action' => 'carry']);

        $credit = GuestCredit::where('reservation_id', $reservation->id)->firstOrFail();
        $staff  = User::role('receptionist')->firstOrFail();

        $this->actingAs($staff)->get(route('guest-credits.index'))->assertOk();
        // الحارس يُرجع الموظف بصفحة مفهومة بدل شاشة 403 خام
        $this->actingAs($staff)
            ->post(route('guest-credits.settle', $credit), ['settlement_method' => 'cash'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(GuestCredit::STATUS_OPEN, $credit->refresh()->status);
    }
}
