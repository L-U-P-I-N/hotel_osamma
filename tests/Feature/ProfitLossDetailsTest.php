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
 * الأرباح والخسائر تعرض مصدر كل ريال — ممّن قُبض، ولأي حجز، ومَن استلمه —
 * لا مجرد إجمالي لكل طريقة دفع. وصفحة تكامل البيانات تُظهر لكل سحبية يتيمة
 * مَن نفّذها وفي أي وردية كان يفترض أن تُسجَّل.
 */
class ProfitLossDetailsTest extends TestCase
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

    private function paidReservation(User $receiver, Shift $shift): Payment
    {
        $guest = Guest::create([
            'full_name' => 'نزيل الاختبار', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '0199' . random_int(10000, 99999),
        ]);

        $reservation = Reservation::create([
            'guest_id'       => $guest->id,
            'room_id'        => Room::where('status', 'available')->firstOrFail()->id,
            'created_by'     => $receiver->id,
            'check_in_date'  => today(),
            'check_out_date' => today()->addDay(),
            'check_in_time'  => '14:00',
            'check_out_time' => '13:00',
            'status'         => 'checked_in',
            'payment_status' => 'partial',
            'total_amount'   => 30000,
        ]);

        return Payment::create([
            'reservation_id' => $reservation->id,
            'shift_id'       => $shift->id,
            'received_by'    => $receiver->id,
            'paid_by_name'   => 'أخو النزيل صالح',
            'amount'         => 12000,
            'currency'       => 'YER',
            'method'         => 'cash',
            'payment_date'   => now(),
            'type'           => 'advance',
        ]);
    }

    public function test_profit_loss_lists_every_payment_with_payer_and_receiver(): void
    {
        $admin = $this->admin();
        $this->paidReservation($admin, $this->openShift($admin));

        $response = $this->actingAs($admin)->get(route('reports.profitLoss', ['preset' => 'month']));

        $response->assertOk()
            ->assertSee('تفاصيل الإيرادات (كل دفعة)')
            ->assertSee('أخو النزيل صالح')   // ممّن قُبض المبلغ
            ->assertSee('نزيل الاختبار')      // عن أي نزيل
            ->assertSee($admin->name);        // مَن استلمه

        $this->assertContains(
            'أخو النزيل صالح',
            $response->viewData('revenueDetails')->pluck('paid_by_name')->all()
        );
        $this->assertContains(
            $admin->name,
            $response->viewData('revenueByReceiver')->pluck('name')->all()
        );
    }

    public function test_orphan_withdrawals_show_who_performed_them_and_the_candidate_shift(): void
    {
        $admin = $this->admin();
        $shift = $this->openShift($admin); // وردية نفس اليوم = الوردية المرشَّحة

        CashWithdrawal::create([
            'shift_id'           => null,
            'amount'             => 4000,
            'currency'           => 'YER',
            'withdrawal_date'    => now(),
            'withdrawn_by_name'  => 'سالم المستلم',
            'handed_by_name'     => 'فهد المُسلِّم',
            'notes'              => 'سحب بلا وردية',
            'withdrawal_type'    => 'expense',
            'funding_source'     => 'general_safe',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.financialIntegrity'))
            ->assertOk()
            ->assertSee('سالم المستلم')
            ->assertSee('فهد المُسلِّم')
            ->assertSee('الصندوق العام')
            ->assertSee('الوردية المرشَّحة')
            ->assertSee($shift->user->name);
    }
}
