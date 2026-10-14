<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * وردية أُقفلت ثم أُعيد فتحها مفتوحةٌ للتصحيح لا للعمل.
 *
 * كانت getActiveShift تُرجع أيّ وردية غير مقفلة بغضّ النظر عن تاريخها ولا عن
 * سبب فتحها، فوردية الأمس المُعاد فتحها تعود «الوردية المفتوحة» للموظف فتمتصّ
 * مستلمات اليوم ومصروفاته بلا أثرٍ يدلّ. فيُفاجأ عند إقفالها ثانياً بمالٍ لم
 * يَعُدّه، ويُخلط إيراد يومين في إقفال واحد.
 */
class ReopenedShiftIsNotForWorkTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function service(): ShiftService { return app(ShiftService::class); }

    /** وردية أمس أُقفلت ثم أُعيد فتحها — عبر الخدمة نفسها لا بتعديل الأعمدة يدوياً. */
    private function reopenedShift(): Shift
    {
        $shift = Shift::create([
            'user_id'    => $this->admin()->id, 'shift_date' => today()->subDay(),
            'started_at' => now()->subDay(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);

        $this->actingAs($this->admin());
        $this->service()->closeShift($shift, '', 0.0);
        $this->service()->reopenShift($shift->refresh(), $this->admin(), 'تصحيح');

        return $shift->refresh();
    }

    private function neverClosedShift(): Shift
    {
        return Shift::create([
            'user_id'    => $this->admin()->id, 'shift_date' => today()->subDay(),
            'started_at' => now()->subDay(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);
    }

    private function reservation(float $amount): Reservation
    {
        $room  = Room::where('room_sub_type', 'regular')->orderBy('room_number')->firstOrFail();
        $guest = Guest::create([
            'full_name' => 'نزيل ' . $amount, 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '04' . random_int(1000000, 9999999),
        ]);

        return Reservation::create([
            'guest_id' => $guest->id, 'room_id' => $room->id, 'created_by' => $this->admin()->id,
            'check_in_date' => today(), 'check_out_date' => today()->addDay(),
            'status' => 'checked_in', 'payment_status' => 'unpaid', 'total_amount' => $amount,
        ]);
    }

    /* ═══ التمييز نفسه ═══ */

    public function test_a_reopened_shift_is_marked_and_a_never_closed_one_is_not(): void
    {
        $this->assertTrue($this->reopenedShift()->was_reopened);
        $this->assertFalse($this->neverClosedShift()->was_reopened);
    }

    public function test_a_reopened_shift_is_open_for_correction_but_not_for_posting(): void
    {
        $shift = $this->reopenedShift();

        // مفتوحة: فلا تُفتح له ثانية ولا يُعاد فتح غيرها
        $this->assertSame($shift->id, $this->service()->getActiveShift($this->admin())?->id);
        // ولا تُقيَّد عليها حركة
        $this->assertNull($this->service()->getPostingShift($this->admin()));
    }

    /** ووردية نُسي إقفالها (لم تُقفل أصلاً) تبقى محلّاً للتسجيل — وإلا بقيت الحركة بلا وردية. */
    public function test_a_never_closed_shift_still_accepts_postings(): void
    {
        $shift = $this->neverClosedShift();

        $this->assertSame($shift->id, $this->service()->getPostingShift($this->admin())?->id);
        $this->assertNull($this->service()->postingBlockReason($this->admin(), 'استلام دفعة'));
    }

    /* ═══ أثره في المال ═══ */

    /** دفعة اليوم لا تُقيَّد على وردية أمس المُعاد فتحها. */
    public function test_a_payment_is_refused_while_the_only_open_shift_was_reopened(): void
    {
        $shift       = $this->reopenedShift();
        $reservation = $this->reservation(30000);

        $response = $this->actingAs($this->admin())->post(route('payments.store'), [
            'reservation_id' => $reservation->id,
            'amount'         => 30000,
            'method'         => 'cash',
        ]);

        $response->assertSessionHasErrors('error');
        $this->assertSame(0, Payment::where('reservation_id', $reservation->id)->count());
        $this->assertEqualsWithDelta(0, $shift->refresh()->total_received_yer, 0.01);
    }

    /** والرسالة تقول ما يُفعل: أقفِلها — لا «افتح وردية» وله واحدة مفتوحة. */
    public function test_the_reason_tells_the_employee_to_close_it_not_to_open_one(): void
    {
        $shift  = $this->reopenedShift();
        $reason = $this->service()->postingBlockReason($this->admin(), 'استلام دفعة');

        $this->assertNotNull($reason);
        $this->assertStringContainsString('أُعيد فتحها', $reason);
        $this->assertStringContainsString($shift->shift_date->format('d/m/Y'), $reason);
        $this->assertStringNotContainsString('دون وردية مفتوحة', $reason);
    }

    /** وبلا أي وردية تبقى الرسالة القديمة: افتح وردية. */
    public function test_with_no_shift_at_all_the_reason_asks_to_open_one(): void
    {
        $reason = $this->service()->postingBlockReason($this->admin(), 'استلام دفعة');

        $this->assertNotNull($reason);
        $this->assertStringContainsString('دون وردية مفتوحة', $reason);
    }

    /** ووردية مفتوحة عادية تستلم كما كانت — لا تراجع في السلوك السليم. */
    public function test_a_normal_open_shift_still_receives_the_payment(): void
    {
        $shift = Shift::create([
            'user_id'    => $this->admin()->id, 'shift_date' => today(),
            'started_at' => now()->subHour(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);
        $reservation = $this->reservation(25000);

        $this->actingAs($this->admin())->post(route('payments.store'), [
            'reservation_id' => $reservation->id,
            'amount'         => 25000,
            'method'         => 'cash',
        ])->assertSessionHasNoErrors();

        $payment = Payment::where('reservation_id', $reservation->id)->firstOrFail();
        $this->assertSame($shift->id, $payment->shift_id);
    }

    /* ═══ الشاشات ═══ */

    /** ولا يُترك الموظف يكتشفها بالمحاولة: لافتة فوق الوردية. */
    public function test_the_shifts_page_warns_that_the_shift_was_reopened(): void
    {
        $this->reopenedShift();

        $this->actingAs($this->admin())->get(route('shifts.index'))
            ->assertOk()
            ->assertSee('أُعيد فتحها للتصحيح', false);
    }

    /** وشاشة التسجيل تعرض السبب نفسه، لا «افتح وردية». */
    public function test_the_checkin_page_shows_the_reopen_reason(): void
    {
        $this->reopenedShift();

        $this->actingAs($this->admin())->get('/checkin')
            ->assertOk()
            ->assertSee('أُعيد فتحها', false);
    }

    /** والإقفال يبقى ممكناً — وإلا لم يكن للموظف مخرج من الحالة. */
    public function test_a_reopened_shift_can_still_be_closed(): void
    {
        $shift = $this->reopenedShift();

        $this->actingAs($this->admin())->post(route('shifts.close'), [
            'actual_amount' => 0,
            'notes'         => 'إقفال بعد التصحيح',
        ])->assertRedirect(route('shifts.index'));

        $this->assertTrue($shift->refresh()->is_closed);
        $this->assertNull($this->service()->getActiveShift($this->admin()));
    }
}
