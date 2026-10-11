<?php

namespace Tests\Feature;

use App\Models\CashWithdrawal;
use App\Models\Expense;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * حذف الوردية بوضعين.
 *
 * كان الحذف وضعاً واحداً: تُحذف الوردية وتبقى مستلماتها ومصروفاتها بلا ربط،
 * ولا سبيل بعدها لإزالتها من الشاشة — فوردية أُدخلت بالكامل خطأً كانت تُخلّف
 * سجلات يتيمة إلى الأبد.
 */
class ShiftDeleteModesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        return User::role('admin')->firstOrFail();
    }

    private function shiftWithRecords(): array
    {
        $admin = $this->admin();

        $shift = Shift::create([
            'user_id' => $admin->id, 'shift_date' => today(),
            'started_at' => now()->subHours(2), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);

        $room  = Room::where('room_sub_type', 'regular')->orderBy('room_number')->firstOrFail();
        $guest = Guest::create([
            'full_name' => 'نزيل الوردية', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '06' . random_int(1000000, 9999999),
        ]);
        $reservation = Reservation::create([
            'guest_id' => $guest->id, 'room_id' => $room->id, 'created_by' => $admin->id,
            'check_in_date' => today(), 'check_out_date' => today()->addDays(2),
            'status' => 'checked_in', 'payment_status' => 'unpaid', 'total_amount' => 30000,
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id, 'shift_id' => $shift->id,
            'amount' => 20000, 'currency' => 'YER', 'method' => 'cash',
            'payment_date' => today(), 'received_by' => $admin->id,
        ]);
        $reservation->refresh()->recalculatePaidAmount();

        $expense = Expense::create([
            'amount' => 5000, 'currency' => 'YER', 'category' => 'other',
            'recipient_name' => 'مورّد', 'expense_date' => today(),
            'paid_by' => $admin->id, 'payment_method' => 'cash', 'shift_id' => $shift->id,
        ]);
        $withdrawal = CashWithdrawal::create([
            'shift_id' => $shift->id, 'expense_id' => $expense->id, 'funding_source' => 'shift',
            'amount' => 5000, 'currency' => 'YER', 'withdrawal_date' => now(),
            'withdrawn_by_name' => 'مورّد', 'handed_by_name' => $admin->name,
            'withdrawal_type' => 'expense',
        ]);

        return compact('shift', 'reservation', 'payment', 'expense', 'withdrawal');
    }

    /* ═══ الوضع الافتراضي: فكّ الربط ═══ */

    public function test_detach_keeps_the_records_unlinked(): void
    {
        ['shift' => $shift, 'payment' => $payment, 'expense' => $expense] = $this->shiftWithRecords();

        $this->actingAs($this->admin())
            ->delete(route('shifts.destroy', $shift), ['mode' => 'detach'])
            ->assertRedirect();

        $this->assertNull(Shift::find($shift->id));
        $this->assertNotNull($payment->fresh(), 'حُذفت المستلمة في وضع فكّ الربط');
        $this->assertNull($payment->fresh()->shift_id);
        $this->assertNull($expense->fresh()->shift_id);
    }

    /** وهو الوضع حين لا يُرسَل شيء — فالسلوك القديم يبقى الافتراضي */
    public function test_detach_is_the_default(): void
    {
        ['shift' => $shift, 'payment' => $payment] = $this->shiftWithRecords();

        $this->actingAs($this->admin())->delete(route('shifts.destroy', $shift))->assertRedirect();

        $this->assertNotNull($payment->fresh());
        $this->assertNull($payment->fresh()->shift_id);
    }

    /* ═══ الوضع الثاني: مع السجلات ═══ */

    public function test_with_records_deletes_them_and_recomputes_the_reservation(): void
    {
        ['shift' => $shift, 'reservation' => $reservation,
         'payment' => $payment, 'expense' => $expense, 'withdrawal' => $withdrawal] = $this->shiftWithRecords();

        $this->assertSame(20000.0, (float) $reservation->fresh()->paid_amount);

        $this->actingAs($this->admin())
            ->delete(route('shifts.destroy', $shift), ['mode' => 'with_records'])
            ->assertRedirect();

        $this->assertNull(Shift::find($shift->id));
        $this->assertNull(Payment::find($payment->id), 'بقيت المستلمة');
        $this->assertNull(Expense::find($expense->id), 'بقي المصروف');
        $this->assertNull(CashWithdrawal::find($withdrawal->id), 'بقي السحب');

        // الحجز لم يعد يبدو مدفوعاً بمالٍ لا أثر له
        $this->assertSame(0.0, (float) $reservation->fresh()->paid_amount, 'لم يُعد احتساب المدفوع');
    }

    /**
     * وفترات تسعير الحجز لا تُحذف في الوضعين: هي أساس فاتورة النزيل لا حركة
     * نقدية للوردية.
     */
    public function test_reservation_segments_are_never_deleted(): void
    {
        ['shift' => $shift, 'reservation' => $reservation] = $this->shiftWithRecords();

        $segment = \App\Models\ReservationSegment::create([
            'reservation_id' => $reservation->id, 'room_id' => $reservation->room_id,
            'type' => 'initial', 'start_date' => today(), 'end_date' => today()->addDays(2),
            'nights' => 2, 'price_per_night' => 15000, 'amount' => 30000,
            'created_by' => $this->admin()->id, 'shift_id' => $shift->id,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('shifts.destroy', $shift), ['mode' => 'with_records'])
            ->assertRedirect();

        $this->assertNotNull($segment->fresh(), 'حُذفت فترة تسعير الحجز');
        $this->assertNull($segment->fresh()->shift_id);
    }

    /* ═══ الصلاحية والتحقق ═══ */

    /** حذف السجلات مقصور على المدير — فكّ الربط يُصحَّح، والحذف لا رجعة فيه */
    public function test_a_non_admin_cannot_delete_the_records(): void
    {
        ['shift' => $shift, 'payment' => $payment] = $this->shiftWithRecords();

        $staff = User::role('receptionist')->firstOrFail();
        \App\Models\UserPermission::updateOrCreate(
            ['user_id' => $staff->id, 'permission_key' => 'shifts.delete'],
            ['is_granted' => true, 'granted_at' => now()]
        );
        $shift->update(['user_id' => $staff->id]);

        $this->actingAs($staff->fresh())
            ->delete(route('shifts.destroy', $shift), ['mode' => 'with_records'])
            ->assertSessionHasErrors('error');

        $this->assertNotNull(Shift::find($shift->id), 'حُذفت الوردية رغم رفض الصلاحية');
        $this->assertNotNull($payment->fresh());
    }

    /* ═══ تنظيف اليتامى الموجودين سلفاً ═══ */

    /** بقايا وردية حُذفت سابقاً: لم يكن لها من الشاشة إلا الضمّ */
    public function test_orphan_records_can_be_deleted(): void
    {
        ['shift' => $shift, 'reservation' => $reservation,
         'payment' => $payment, 'expense' => $expense, 'withdrawal' => $withdrawal] = $this->shiftWithRecords();

        // حذفٌ بفكّ الربط يُخلّفهم يتامى
        $this->actingAs($this->admin())->delete(route('shifts.destroy', $shift));
        $this->assertNull($payment->fresh()->shift_id);

        $this->actingAs($this->admin())
            ->delete(route('shifts.destroyOrphans'), [
                'payment_ids'    => [$payment->id],
                'withdrawal_ids' => [$withdrawal->id],
            ])
            ->assertSessionHasNoErrors();

        $this->assertNull(Payment::find($payment->id));
        $this->assertNull(CashWithdrawal::find($withdrawal->id));
        // والمصروف المرتبط بالسحب يرحل معه، وإلا بقي رقمٌ بلا سحب يقابله
        $this->assertNull(Expense::find($expense->id), 'بقي المصروف المرتبط');
        $this->assertSame(0.0, (float) $reservation->fresh()->paid_amount);
    }

    /** ولا تُمسّ سجلات ما زالت مرتبطة بوردية ولو مُرّرت معرّفاتها */
    public function test_a_linked_record_is_not_deleted_by_the_orphan_cleanup(): void
    {
        ['payment' => $payment, 'withdrawal' => $withdrawal] = $this->shiftWithRecords();

        $this->actingAs($this->admin())
            ->delete(route('shifts.destroyOrphans'), [
                'payment_ids'    => [$payment->id],
                'withdrawal_ids' => [$withdrawal->id],
            ]);

        $this->assertNotNull(Payment::find($payment->id), 'حُذفت مستلمة مرتبطة بوردية');
        $this->assertNotNull(CashWithdrawal::find($withdrawal->id));
    }

    public function test_an_unknown_mode_is_refused(): void
    {
        ['shift' => $shift] = $this->shiftWithRecords();

        $this->actingAs($this->admin())
            ->delete(route('shifts.destroy', $shift), ['mode' => 'nuke'])
            ->assertSessionHasErrors('mode');

        $this->assertNotNull(Shift::find($shift->id));
    }
}
