<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ملاحظة تسجيل الخروج: تُكتب لحظة المغادرة وتظهر في كشوف النزلاء — الموجودين
 * والمغادرين — فيقرأها من يراجع الكشف دون فتح كل حجز على حِدة.
 */
class CheckoutNoteInGuestListTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function stay(): Reservation
    {
        $guest = Guest::create([
            'full_name' => 'نزيل ملاحظة الخروج', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '04' . random_int(1000000, 9999999),
        ]);

        return Reservation::create([
            'guest_id'   => $guest->id,
            'room_id'    => Room::where('status', 'available')->firstOrFail()->id,
            'created_by' => $this->admin()->id,
            'check_in_date' => today(), 'check_in_time' => '14:00',
            'check_out_date' => today()->addDay(), 'check_out_time' => '13:00',
            'status' => 'checked_in', 'payment_status' => 'paid',
            'total_amount' => 20000, 'paid_amount' => 20000,
        ]);
    }

    public function test_the_checkout_screen_has_a_note_field(): void
    {
        $this->actingAs($this->admin())
            ->get(route('checkout.show', $this->stay()))
            ->assertOk()
            ->assertSee('ملاحظة الخروج', false)
            ->assertSee('name="checkout_notes"', false);
    }

    public function test_the_note_is_saved_with_the_checkout(): void
    {
        $reservation = $this->stay();

        $this->actingAs($this->admin())
            ->post(route('checkout.process', $reservation), [
                'checkout_notes' => 'الغرفة سليمة — وعد بالعودة الأسبوع القادم',
            ])
            ->assertRedirect(route('checkout.done', $reservation->id));

        $this->assertSame(
            'الغرفة سليمة — وعد بالعودة الأسبوع القادم',
            $reservation->refresh()->checkout_notes
        );
    }

    /** ملاحظة من مسافات فقط لا تُحفظ، فتبقى الكشوف نظيفة. */
    public function test_a_blank_note_is_not_stored(): void
    {
        $reservation = $this->stay();

        $this->actingAs($this->admin())
            ->post(route('checkout.process', $reservation), ['checkout_notes' => '   ']);

        $this->assertNull($reservation->refresh()->checkout_notes);
    }

    /** كشف الحجوزات يضمّ المغادرين، فالملاحظة تُقرأ منه بعد المغادرة. */
    public function test_it_shows_in_the_reservations_list_of_departed_guests(): void
    {
        $reservation = $this->stay();

        $this->actingAs($this->admin())
            ->post(route('checkout.process', $reservation), ['checkout_notes' => 'غادر بعد تسوية كل المستحقات']);

        $this->actingAs($this->admin())
            ->get(route('reports.dailyHub', [
                'tab' => 'reservations',
                'from' => today()->toDateString(), 'to' => today()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('غادر بعد تسوية كل المستحقات', false);
    }

    /**
     * وكشف النزلاء الموجودين كذلك: خروجٌ سُجّل بالغلط وتُرِاجع عنه يُبقي ملاحظته،
     * فتظهر للنزيل الحاضر أيضاً بدل أن تضيع.
     */
    public function test_it_shows_in_the_present_guests_list_too(): void
    {
        $reservation = $this->stay();

        $this->actingAs($this->admin())
            ->post(route('checkout.process', $reservation), ['checkout_notes' => 'خرج ثم عاد — نفس الغرفة']);

        $this->actingAs($this->admin())->patch(route('checkout.undo', $reservation));

        $this->assertSame('checked_in', $reservation->refresh()->status);

        $this->actingAs($this->admin())
            ->get(route('reports.dailyHub', ['date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee('خرج ثم عاد — نفس الغرفة', false);
    }

    public function test_it_shows_on_the_reservation_page_and_the_done_screen(): void
    {
        $reservation = $this->stay();

        $this->actingAs($this->admin())
            ->post(route('checkout.process', $reservation), ['checkout_notes' => 'سلّم المفتاح للاستقبال']);

        $this->actingAs($this->admin())->get(route('checkout.done', $reservation))
            ->assertOk()->assertSee('سلّم المفتاح للاستقبال', false);

        $this->actingAs($this->admin())->get(route('reservations.show', $reservation))
            ->assertOk()->assertSee('سلّم المفتاح للاستقبال', false);
    }

    /** ولا تُحجب عن كشف يضمّ النزلاء الموجودين: الملاحظة تُطبع في PDF أيضاً. */
    public function test_it_reaches_the_printed_guest_list(): void
    {
        $reservation = $this->stay();

        $this->actingAs($this->admin())
            ->post(route('checkout.process', $reservation), ['checkout_notes' => 'ملاحظة مطبوعة']);

        $html = view('reports.reservations_pdf', [
            'reservations'     => Reservation::with(['guest', 'room', 'payments', 'createdBy', 'checkedOutBy'])->get(),
            'from'             => today()->toDateString(),
            'to'               => today()->toDateString(),
            'selectedColumns'  => ['notes'],
            'total'            => 1,
            'checkedIn'        => 0,
            'checkedOut'       => 1,
            'printedCount'     => 1,
            'status'           => 'all',
        ])->render();

        $this->assertStringContainsString('[خروج]', $html);
    }

    /** الحدّ الأقصى محروس برسالة عربية مفهومة. */
    public function test_an_over_long_note_is_refused_in_arabic(): void
    {
        $reservation = $this->stay();

        $this->actingAs($this->admin())
            ->post(route('checkout.process', $reservation), ['checkout_notes' => str_repeat('ا', 1001)])
            ->assertSessionHasErrors('checkout_notes');

        $this->assertSame('checked_in', $reservation->refresh()->status);
    }
}
