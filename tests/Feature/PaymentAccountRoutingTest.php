<?php
namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Guest;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * توجيه كل مقبوض إلى وعائه المالي الحقيقي.
 *
 * كانت كل دفعة تُرحَّل إلى درج نقدية الوردية مهما كانت طريقتها، فالتحويل البنكي
 * يظهر نقداً في الدرج، ويُطالَب الموظف عند الإقفال بنقدٍ لم تصل يدَه، ولا سبيل
 * لمعرفة رصيد كل حساب بنكي على حِدة.
 */
class PaymentAccountRoutingTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function shift(): Shift
    {
        return Shift::firstOrCreate(
            ['user_id' => $this->admin()->id, 'shift_date' => today(), 'is_closed' => false],
            ['started_at' => now()->subHour(), 'opening_balance_yer' => 0]
        );
    }

    private function stay(float $total = 100000): Reservation
    {
        $guest = Guest::create([
            'full_name' => 'نزيل الدفع', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '04' . random_int(1000000, 9999999),
        ]);

        return Reservation::create([
            'guest_id' => $guest->id,
            'room_id'  => Room::where('status', 'available')->firstOrFail()->id,
            'created_by' => $this->admin()->id,
            'check_in_date' => today(), 'check_out_date' => today()->addDays(2),
            'status' => 'checked_in', 'payment_status' => 'unpaid',
            'total_amount' => $total, 'paid_amount' => 0,
        ]);
    }

    private function pay(Reservation $reservation, string $method, float $amount, ?int $accountId = null): Payment
    {
        return app(PaymentService::class)->addPayment($reservation, [
            'amount' => $amount, 'currency' => 'YER', 'method' => $method,
            'payment_account_id' => $accountId, 'type' => 'reservation',
        ], $this->admin());
    }

    /* ═══ الأوعية الافتراضية ═══ */

    public function test_the_default_containers_exist_and_map_to_the_ledger(): void
    {
        foreach (['shift_cash' => '1111', 'safe' => '1120', 'bank' => '1131', 'pos' => '1140'] as $type => $code) {
            $account = PaymentAccount::where('type', $type)->firstOrFail();

            $this->assertSame($code, $account->account_code, $type);
            $this->assertTrue($account->ledgerAccount->is_posting, "حساب {$code} يجب أن يقبل الترحيل");
        }

        $this->assertTrue(PaymentAccount::where('type', 'shift_cash')->first()->is_cash);
        $this->assertFalse(PaymentAccount::where('type', 'bank')->first()->is_cash);
    }

    /* ═══ الترحيل المحاسبي ═══ */

    public function test_a_cash_payment_lands_in_the_drawer_account(): void
    {
        $payment = $this->pay($this->stay(), 'cash', 40000);

        $entry = JournalEntry::where('source_type', Payment::class)->where('source_id', $payment->id)->firstOrFail();

        $this->assertEqualsWithDelta(40000, $entry->lines()->where('account_code', '1111')->sum('debit'), 0.01);
        $this->assertSame(PaymentAccount::where('type', 'shift_cash')->first()->id, $payment->payment_account_id);
    }

    /** والتحويل البنكي يذهب إلى البنك لا إلى الدرج — جوهر الإصلاح. */
    public function test_a_bank_transfer_lands_in_the_bank_account_not_the_drawer(): void
    {
        $payment = $this->pay($this->stay(), 'bank_transfer', 60000);

        $entry = JournalEntry::where('source_type', Payment::class)->where('source_id', $payment->id)->firstOrFail();

        $this->assertEqualsWithDelta(60000, $entry->lines()->where('account_code', '1131')->sum('debit'), 0.01);
        $this->assertSame(0, $entry->lines()->where('account_code', '1111')->count(), 'التحويل البنكي سُجّل في درج الوردية');
    }

    public function test_a_card_payment_lands_in_the_card_receivable_account(): void
    {
        $payment = $this->pay($this->stay(), 'pos', 25000);

        $entry = JournalEntry::where('source_type', Payment::class)->where('source_id', $payment->id)->firstOrFail();

        $this->assertEqualsWithDelta(25000, $entry->lines()->where('account_code', '1140')->sum('debit'), 0.01);
    }

    /* ═══ أكثر من حساب بنكي ═══ */

    public function test_each_bank_account_keeps_its_own_balance(): void
    {
        $first  = PaymentAccount::where('type', 'bank')->firstOrFail();
        $ledger = PaymentAccount::createLedgerAccount('bank', 'بنك ثانٍ');
        $second = PaymentAccount::create([
            'name' => 'بنك ثانٍ', 'type' => 'bank', 'account_code' => $ledger->code,
            'currency' => 'YER', 'is_active' => true, 'sort_order' => 9,
        ]);

        $this->assertNotSame($first->account_code, $second->account_code, 'الحسابان البنكيان يتشاركان حساباً واحداً');

        $this->pay($this->stay(), 'bank_transfer', 70000, $first->id);
        $this->pay($this->stay(), 'bank_transfer', 30000, $second->id);

        $this->assertEqualsWithDelta(70000, $first->refresh()->balance, 0.01);
        $this->assertEqualsWithDelta(30000, $second->refresh()->balance, 0.01);
    }

    /** ووعاء لا يناسب الطريقة يُرفض ويُستبدل بالافتراضي — لا تحويل في درج. */
    public function test_a_container_that_does_not_match_the_method_is_ignored(): void
    {
        $drawer = PaymentAccount::where('type', 'shift_cash')->firstOrFail();

        $payment = $this->pay($this->stay(), 'bank_transfer', 50000, $drawer->id);

        $this->assertNotSame($drawer->id, $payment->payment_account_id);
        $this->assertSame(PaymentAccount::where('type', 'bank')->first()->id, $payment->payment_account_id);
    }

    /* ═══ إقفال الوردية ═══ */

    /**
     * المتوقَّع في الدرج نقدٌ فقط. كان يشمل التحويلات فيظهر الموظف عاجزاً
     * بمقدار ما حُوِّل، وهو عجز ورقيّ لا وجود له.
     */
    public function test_the_expected_drawer_cash_excludes_bank_and_card(): void
    {
        $shift = $this->shift();

        $this->pay($this->stay(), 'cash', 40000);
        $this->pay($this->stay(), 'bank_transfer', 60000);
        $this->pay($this->stay(), 'pos', 25000);

        app(ShiftService::class)->computeTotals($shift);
        $shift->refresh();

        $this->assertEqualsWithDelta(125000, $shift->total_received_yer, 0.01, 'الإجمالي يجب أن يضمّ الكل');
        $this->assertEqualsWithDelta(40000, $shift->total_received_cash_yer, 0.01);
        $this->assertEqualsWithDelta(85000, $shift->total_received_noncash_yer, 0.01);
        // المتوقَّع في الدرج = النقد وحده
        $this->assertEqualsWithDelta(40000, app(ShiftService::class)->expectedDrawerCash($shift), 0.01);
        $this->assertEqualsWithDelta(40000, $shift->net_balance_yer, 0.01);
    }

    /** فموظف سلّم نقده كاملاً لا يظهر عاجزاً لأن نزيلاً حوّل بنكياً. */
    public function test_a_shift_with_a_bank_transfer_closes_without_a_false_shortfall(): void
    {
        $shift = $this->shift();

        $this->pay($this->stay(), 'cash', 30000);
        $this->pay($this->stay(), 'bank_transfer', 200000);

        app(ShiftService::class)->closeShift($shift, '', 30000);

        $this->assertEqualsWithDelta(0, (float) $shift->refresh()->shortfall, 0.01, 'ظهر عجز ورقيّ بسبب التحويل البنكي');
    }

    /* ═══ الشاشة ═══ */

    public function test_the_containers_screen_shows_each_balance(): void
    {
        $this->pay($this->stay(), 'bank_transfer', 90000);

        $this->actingAs($this->admin())
            ->get(route('payment-accounts.index'))
            ->assertOk()
            ->assertSee('الحساب البنكي الرئيسي', false)
            ->assertSee('درج نقدية الوردية', false)
            ->assertSee('90,000');
    }

    public function test_adding_a_bank_account_creates_its_ledger_account(): void
    {
        $before = ChartOfAccount::count();

        $this->actingAs($this->admin())
            ->post(route('payment-accounts.store'), [
                'name' => 'بنك الكريمي — الجاري', 'type' => 'bank', 'bank_name' => 'الكريمي',
            ])
            ->assertRedirect();

        $account = PaymentAccount::where('name', 'بنك الكريمي — الجاري')->firstOrFail();

        $this->assertSame($before + 1, ChartOfAccount::count(), 'لم يُنشأ حساب في الشجرة للوعاء الجديد');
        $this->assertTrue($account->ledgerAccount->is_posting);
        $this->assertSame('1130', $account->ledgerAccount->parent_code, 'الحساب البنكي يجب أن يكون تحت البنوك');
    }

    /** ووعاء له حركة لا يُحذف بل يُعطَّل، فلا ينكسر أثر قيوده. */
    public function test_a_used_container_is_disabled_not_deleted(): void
    {
        $bank = PaymentAccount::where('type', 'bank')->firstOrFail();
        $this->pay($this->stay(), 'bank_transfer', 10000, $bank->id);

        $this->actingAs($this->admin())
            ->patch(route('payment-accounts.toggle', $bank))
            ->assertRedirect();

        $this->assertFalse($bank->refresh()->is_active);
        $this->assertDatabaseHas('payment_accounts', ['id' => $bank->id]);
        $this->assertEqualsWithDelta(10000, $bank->balance, 0.01, 'ضاع رصيد الوعاء بعد تعطيله');
    }
}
