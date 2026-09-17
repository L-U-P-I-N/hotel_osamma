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
 * نزيل غادر دون سداد ثم عاد: الموظف الجديد لا يعرفه، فيجب أن يُنبَّه بالدَّين
 * القديم وقت تسجيل الدخول، وأن يرى في تفاصيل الحجز الإجمالي شاملاً الدَّين.
 * الدَّين يبقى محاسبياً على حجزه الأصلي فلا يُحتسب مرتين.
 */
class ReturningGuestDebtTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function adminUser(): User { return User::role('admin')->firstOrFail(); }

    protected function makeGuest(string $name = 'نزيل مديون'): Guest
    {
        return Guest::create([
            'full_name' => $name, 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '07' . random_int(1000000, 9999999),
        ]);
    }

    /**
     * إقامة بمبلغ مدفوع حقيقي: الحجز يعيد احتساب "المدفوع" من سجلات الدفعات،
     * فضبط الحقل وحده دون دفعة يُمحى عند أول إعادة احتساب.
     */
    protected function makeStay(Guest $guest, string $status, float $total, float $paid): Reservation
    {
        $reservation = Reservation::create([
            'guest_id'       => $guest->id,
            'room_id'        => Room::where('status', 'available')->firstOrFail()->id,
            'created_by'     => $this->adminUser()->id,
            'check_in_date'  => today()->subDays(10),
            'check_out_date' => today()->subDays(8),
            'check_in_time'  => '14:00',
            'check_out_time' => '13:00',
            'status'         => $status,
            'payment_status' => $paid >= $total ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
            'total_amount'   => $total,
            'paid_amount'    => $paid,
        ]);

        if ($paid > 0) {
            \App\Models\Payment::create([
                'reservation_id' => $reservation->id,
                'received_by'    => $this->adminUser()->id,
                'amount'         => $paid,
                'currency'       => 'YER',
                'method'         => 'cash',
                'payment_date'   => $reservation->check_in_date,
                'type'           => 'reservation',
            ]);
        }

        return $reservation;
    }

    public function test_debt_totals_only_count_departed_unpaid_stays(): void
    {
        $guest = $this->makeGuest();
        $this->makeStay($guest, 'checked_out', 40000, 10000);  // دَين 30,000
        $this->makeStay($guest, 'checked_out', 20000, 20000);  // مسدَّد — لا يُحتسب
        $this->makeStay($guest, 'cancelled',   15000, 0);      // ملغى — لا يُحتسب
        $current = $this->makeStay($guest, 'checked_in', 50000, 0); // إقامة قائمة — ليست ديناً سابقاً

        $this->assertSame(30000.0, $guest->previousDebtTotal());
        $this->assertSame(30000.0, $guest->previousDebtTotal($current->id));
        $this->assertCount(1, $guest->unpaidPreviousStays()->get());
    }

    public function test_guest_search_reports_the_debt_to_the_receptionist(): void
    {
        $guest = $this->makeGuest('سالم المديون');
        $this->makeStay($guest, 'checked_out', 40000, 10000);

        $this->actingAs($this->adminUser())
            ->getJson(route('guests.search', ['q' => 'سالم']))
            ->assertOk()
            ->assertJsonFragment(['debt_total' => 30000.0, 'debt_count' => 1]);
    }

    public function test_guest_without_debt_reports_zero(): void
    {
        $guest = $this->makeGuest('سالم النظيف');
        $this->makeStay($guest, 'checked_out', 20000, 20000);

        $this->actingAs($this->adminUser())
            ->getJson(route('guests.search', ['q' => 'سالم']))
            ->assertOk()
            ->assertJsonFragment(['debt_total' => 0.0, 'debt_count' => 0]);
    }

    public function test_reservation_page_shows_the_previous_debt_and_combined_total(): void
    {
        $guest = $this->makeGuest();
        $this->makeStay($guest, 'checked_out', 40000, 10000);   // دَين 30,000
        $current = $this->makeStay($guest, 'checked_in', 50000, 20000); // متبقٍ 30,000

        $response = $this->actingAs($this->adminUser())->get(route('reservations.show', $current));

        $response->assertOk()
            ->assertSee('على هذا النزيل دَين سابق لم يُسدَّد')
            ->assertSee('الإجمالي المستحق على النزيل')
            ->assertSee('60,000');   // 30,000 متبقي الحجز + 30,000 دَين سابق

        $this->assertSame(30000.0, $response->viewData('previousDebtTotal'));

        // الدَّين لم يُنقل: إجمالي الحجز الجديد كما هو، والقديم كما هو
        $this->assertSame(50000.0, (float) $current->fresh()->total_amount);
    }

    public function test_no_panel_when_the_guest_owes_nothing(): void
    {
        $guest = $this->makeGuest();
        $current = $this->makeStay($guest, 'checked_in', 50000, 0);

        $this->actingAs($this->adminUser())
            ->get(route('reservations.show', $current))
            ->assertOk()
            ->assertDontSee('على هذا النزيل دَين سابق لم يُسدَّد');
    }

    public function test_collecting_the_old_debt_credits_the_old_stay_and_returns_to_the_current_one(): void
    {
        $admin = $this->adminUser();
        Shift::create([
            'user_id' => $admin->id, 'shift_date' => today(),
            'started_at' => now()->subHour(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);

        $guest   = $this->makeGuest();
        $old     = $this->makeStay($guest, 'checked_out', 40000, 10000);
        $current = $this->makeStay($guest, 'checked_in', 50000, 0);

        $this->actingAs($admin)
            ->post(route('payments.store'), [
                'reservation_id' => $old->id,
                'amount'         => 30000,
                'method'         => 'cash',
                'return_to'      => $current->id,
            ])
            ->assertRedirect(route('reservations.show', $current));

        // الدفعة قُيّدت على الحجز القديم وحده
        $this->assertSame(40000.0, (float) $old->fresh()->paid_amount);
        $this->assertSame(0.0, (float) $current->fresh()->paid_amount);
        $this->assertSame(0.0, $guest->fresh()->previousDebtTotal());
    }
}
