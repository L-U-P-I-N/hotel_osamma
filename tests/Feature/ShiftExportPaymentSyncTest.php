<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftExportPaymentSyncTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function openShiftFor(User $user): Shift
    {
        return Shift::create([
            'user_id'             => $user->id,
            'shift_date'          => today(),
            'started_at'          => now()->subHour(),
            'is_closed'           => false,
            'opening_balance_yer' => 0,
        ]);
    }

    private function makeReservation(User $creator, float $total): Reservation
    {
        return Reservation::create([
            'guest_id'       => Guest::firstOrFail()->id,
            'room_id'        => Room::where('status', 'available')->firstOrFail()->id,
            'created_by'     => $creator->id,
            'check_in_date'  => today(),
            'check_out_date' => today()->addDays(2),
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => $total,
            'paid_amount'    => 0,
        ]);
    }

    public function test_deleting_a_payment_updates_status_and_balance_everywhere(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $this->openShiftFor($admin);

        $reservation = $this->makeReservation($admin, 20000);
        $payment = app(PaymentService::class)->addPayment($reservation, [
            'amount' => 20000, 'method' => 'cash', 'currency' => 'YER',
        ], $admin);

        $reservation->refresh();
        $this->assertSame('paid', $reservation->payment_status);
        $this->assertEqualsWithDelta(0, $reservation->balance, 0.01);

        $this->actingAs($admin)->delete("/payments/{$payment->id}")->assertRedirect();

        $reservation->refresh();
        $this->assertEqualsWithDelta(0, (float) $reservation->paid_amount, 0.01, 'المدفوع يجب أن يعود صفراً');
        $this->assertSame('unpaid', $reservation->payment_status, 'حالة الدفع يجب أن تعود غير مدفوع');
        $this->assertEqualsWithDelta(20000, $reservation->balance, 0.01, 'المتبقي يجب أن يعود كامل المبلغ');

        // تصدير الوردية يجب أن يعكس نفس الأرقام
        $shift = Shift::where('user_id', $admin->id)->firstOrFail();
        $response = $this->actingAs($admin)->get("/shifts/{$shift->id}/pdf");
        $response->assertOk();
    }

    /**
     * جوهر البلاغ: عمودا "حالة الدفع" و"المتبقّي" في جدول النزلاء المسجّلين
     * داخل تصدير الوردية يجب أن يعكسا حذف الدفعة فوراً.
     */
    public function test_shift_export_table_reflects_the_deleted_payment(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $shift = $this->openShiftFor($admin);

        $reservation = $this->makeReservation($admin, 20000);
        $payment = app(PaymentService::class)->addPayment($reservation, [
            'amount' => 20000, 'method' => 'cash', 'currency' => 'YER',
        ], $admin);

        $renderExport = function () use ($shift) {
            $shift->refresh()->load(['user', 'payments.reservation.guest', 'payments.reservation.room', 'withdrawals', 'refunds']);
            $guests = Reservation::with(['guest', 'room'])
                ->where('created_by', $shift->user_id)
                ->where('created_at', '>=', $shift->started_at)
                ->get();

            return view('shifts.report_pdf', [
                'shift'                  => $shift,
                'checkedInGuests'        => $guests,
                'generalSafeWithdrawals' => collect(),
            ])->render();
        };

        $before = $renderExport();
        $this->assertStringContainsString('مدفوع', $before);

        $this->actingAs($admin)->delete("/payments/{$payment->id}")->assertRedirect();

        $after = $renderExport();
        $this->assertStringContainsString('غير مدفوع', $after, 'حالة الدفع يجب أن تظهر "غير مدفوع" بعد حذف الدفعة');
        $this->assertStringContainsString('20,000', $after, 'المتبقّي يجب أن يظهر كمديونية');
    }

    public function test_refund_reduces_paid_amount_but_cancellation_refund_does_not(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $this->openShiftFor($admin);

        // استرجاع عادي: يُخصم من المدفوع
        $a = $this->makeReservation($admin, 20000);
        app(PaymentService::class)->addPayment($a, ['amount' => 20000, 'method' => 'cash', 'currency' => 'YER'], $admin);
        app(\App\Services\RefundService::class)->createRefund($a, [
            'amount' => 8000, 'currency' => 'YER', 'method' => 'cash', 'reason' => 'استرجاع جزئي',
        ], $admin);

        $a->refresh();
        $this->assertEqualsWithDelta(12000, (float) $a->paid_amount, 0.01);
        $this->assertSame('partial', $a->payment_status);
        $this->assertEqualsWithDelta(8000, $a->balance, 0.01);

        // استرجاع الإلغاء: لا يُخصم — المدفوع يبقى سجلاً تاريخياً
        $b = $this->makeReservation($admin, 20000);
        app(PaymentService::class)->addPayment($b, ['amount' => 20000, 'method' => 'cash', 'currency' => 'YER'], $admin);
        app(\App\Services\RefundService::class)->createRefund($b, [
            'amount' => 20000, 'currency' => 'YER', 'method' => 'cash', 'reason' => 'إلغاء',
        ], $admin, adjustPaidAmount: false);

        $b->refresh();
        $this->assertEqualsWithDelta(20000, (float) $b->paid_amount, 0.01, 'استرجاع الإلغاء يجب ألا يغيّر المدفوع');
        $this->assertSame('paid', $b->payment_status);
    }

    /** أي انحراف قديم بين المدفوع والدفعات الفعلية يُكشف ويُصحَّح */
    public function test_reconcile_command_detects_and_repairs_drift(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $this->openShiftFor($admin);

        $reservation = $this->makeReservation($admin, 30000);
        $payment = app(PaymentService::class)->addPayment($reservation, [
            'amount' => 30000, 'method' => 'cash', 'currency' => 'YER',
        ], $admin);

        // محاكاة ما تركته النسخ القديمة: دفعة محذوفة والمدفوع لم يتغيّر
        \Illuminate\Support\Facades\DB::table('payments')
            ->where('id', $payment->id)->update(['deleted_at' => now()]);

        $reservation->refresh();
        $this->assertSame('paid', $reservation->payment_status, 'الحالة الخاطئة قبل التصحيح');

        $this->artisan('payments:reconcile')->assertSuccessful();
        $this->assertSame('paid', $reservation->fresh()->payment_status, 'العرض وحده لا يغيّر شيئاً');

        $this->artisan('payments:reconcile', ['--fix' => true])->assertSuccessful();

        $reservation->refresh();
        $this->assertEqualsWithDelta(0, (float) $reservation->paid_amount, 0.01);
        $this->assertSame('unpaid', $reservation->payment_status);
        $this->assertEqualsWithDelta(30000, $reservation->balance, 0.01);
    }

    public function test_correcting_a_payment_amount_updates_status_and_balance(): void
    {
        $admin = User::role('admin')->firstOrFail();
        $this->openShiftFor($admin);

        $reservation = $this->makeReservation($admin, 20000);
        $payment = app(PaymentService::class)->addPayment($reservation, [
            'amount' => 20000, 'method' => 'cash', 'currency' => 'YER',
        ], $admin);

        $this->assertSame('paid', $reservation->fresh()->payment_status);

        $this->actingAs($admin)->patch("/payments/{$payment->id}", [
            'amount'            => 5000,
            'correction_reason' => 'خطأ في الإدخال',
        ])->assertSessionHasNoErrors();

        $reservation->refresh();
        $this->assertEqualsWithDelta(5000, (float) $reservation->paid_amount, 0.01);
        $this->assertSame('partial', $reservation->payment_status);
        $this->assertEqualsWithDelta(15000, $reservation->balance, 0.01);
    }
}
