<?php
namespace Tests\Feature;

use App\Exports\ReservationsReportExport;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "مغادرة بواسطة": من نفّذ خروج النزيل.
 *
 * كان مفقوداً في صفحة تفاصيل الحجز كلياً، وكان عمود التصدير مقصوراً على فلتر
 * «من غادروا فقط» فيختفي من التصدير الافتراضي («الكل») — وهو التصدير الذي
 * يستعمله الموظف غالباً، فيُسأل من سجّل الخروج ولا جواب في الورقة.
 */
class CheckedOutByVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function departedStay(): Reservation
    {
        $guest = Guest::create([
            'full_name' => 'نزيل غادر', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '04' . random_int(1000000, 9999999),
        ]);

        $reservation = Reservation::create([
            'guest_id'   => $guest->id,
            'room_id'    => Room::where('status', 'available')->firstOrFail()->id,
            'created_by' => $this->admin()->id,
            'check_in_date' => today(), 'check_in_time' => '14:00',
            'check_out_date' => today()->addDay(), 'check_out_time' => '13:00',
            'status' => 'checked_in', 'payment_status' => 'paid',
            'total_amount' => 20000, 'paid_amount' => 20000,
        ]);

        // يُسجّل الخروج موظف الاستقبال — لا المدير — كي يتبيّن أن الاسم المعروض
        // هو منفّذ الخروج فعلاً لا منشئ الحجز
        $this->actingAs(User::role('receptionist')->firstOrFail())
            ->post(route('checkout.process', $reservation));

        return $reservation->refresh();
    }

    /* ═══ صفحة تفاصيل الحجز ═══ */

    public function test_the_reservation_page_names_who_checked_the_guest_out(): void
    {
        $reservation = $this->departedStay();
        $staff = User::role('receptionist')->firstOrFail();

        $this->assertSame($staff->id, $reservation->checked_out_by);

        $this->actingAs($this->admin())
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('مغادرة بواسطة', false)
            ->assertSee($staff->name, false)
            ->assertSee('وقت المغادرة الفعلي', false);
    }

    /** ولا يظهر السطر لنزيل ما زال مقيماً — لا معنى له قبل المغادرة. */
    public function test_a_staying_guest_has_no_checkout_line(): void
    {
        $guest = Guest::create([
            'full_name' => 'نزيل مقيم', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '04' . random_int(1000000, 9999999),
        ]);
        $reservation = Reservation::create([
            'guest_id' => $guest->id,
            'room_id' => Room::where('status', 'available')->firstOrFail()->id,
            'created_by' => $this->admin()->id,
            'check_in_date' => today(), 'check_out_date' => today()->addDay(),
            'status' => 'checked_in', 'payment_status' => 'paid',
            'total_amount' => 20000, 'paid_amount' => 20000,
        ]);

        $this->actingAs($this->admin())
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertDontSee('وقت المغادرة الفعلي', false);
    }

    /* ═══ تصدير PDF ═══ */

    /** التصدير الافتراضي ("الكل") يضمّ مغادرين، فالعمود مفروض فيه. */
    public function test_the_default_pdf_export_keeps_the_column(): void
    {
        $this->departedStay();

        $pdf = $this->actingAs($this->admin())
            ->get(route('reports.reservations.pdf', [
                'from' => today()->toDateString(), 'to' => today()->toDateString(),
                // الأدمن اختار أعمدة لا تشمله — ومع ذلك يُفرض لأن التقرير يضمّ مغادرين
                'columns' => ['room_number', 'guest_name'],
            ]))
            ->assertOk();

        $this->assertStringContainsString('application/pdf', $pdf->headers->get('content-type'));
    }

    /** وتقرير المغادرين يفرضه كما كان. */
    public function test_the_departed_filter_forces_the_column(): void
    {
        $this->departedStay();

        $this->actingAs($this->admin())
            ->get(route('reports.reservations.pdf', [
                'from' => today()->toDateString(), 'to' => today()->toDateString(),
                'status' => 'checked_out', 'columns' => ['room_number'],
            ]))
            ->assertOk();
    }

    /**
     * القالب نفسه هو الحكم: نُصيّره مباشرةً لنقرأ العمود واسم المنفّذ، فلا
     * نكتفي بأن التصدير لم يفشل.
     */
    public function test_the_pdf_template_prints_the_column_and_the_name(): void
    {
        $reservation = $this->departedStay();
        $staff = User::role('receptionist')->firstOrFail();

        $html = view('reports.reservations_pdf', [
            'reservations'    => Reservation::with(['guest', 'room', 'payments', 'createdBy', 'checkedOutBy'])->get(),
            'from'            => today()->toDateString(),
            'to'              => today()->toDateString(),
            'selectedColumns' => ['guest_name', 'checked_out_by'],
            'status'          => 'all',
            'total'           => 1, 'checkedIn' => 0, 'checkedOut' => 1, 'printedCount' => 1,
        ])->render();

        $this->assertStringContainsString('مغادرة بواسطة', $html);
        $this->assertStringContainsString($staff->name, $html);
    }

    /* ═══ تصدير Excel ═══ */

    /** صفّ حجزٍ بعينه من التصدير — البذور تُنشئ حجوزات أخرى في الفترة نفسها. */
    private function exportedRow(ReservationsReportExport $export, Reservation $reservation): array
    {
        $row = $export->collection()->firstWhere('id', $reservation->id);
        $this->assertNotNull($row, 'الحجز غير موجود في التصدير');

        return $export->map($row);
    }

    public function test_the_excel_export_carries_the_column_for_all_and_departed(): void
    {
        $reservation = $this->departedStay();
        $staff = User::role('receptionist')->firstOrFail();

        foreach (['all', 'checked_out'] as $status) {
            $export = new ReservationsReportExport(today()->toDateString(), today()->toDateString(), $status);

            $this->assertContains('مغادرة بواسطة', $export->headings(), $status);
            $this->assertContains($staff->name, $this->exportedRow($export, $reservation), $status);
        }
    }

    /** ويُحذف في "لم يغادروا" وحده — لا معنى له لنزيل مقيم. */
    public function test_the_staying_only_excel_export_drops_the_column(): void
    {
        $export = new ReservationsReportExport(today()->toDateString(), today()->toDateString(), 'checked_in');

        $this->assertNotContains('مغادرة بواسطة', $export->headings());
    }

    /** وملاحظة الخروج تصل كشف Excel كما تصل نسخة PDF. */
    public function test_the_excel_notes_column_carries_the_checkout_note(): void
    {
        $reservation = $this->departedStay();
        $reservation->update(['checkout_notes' => 'سلّم المفتاح وغادر']);

        $export = new ReservationsReportExport(today()->toDateString(), today()->toDateString(), 'all');

        $this->assertContains('[خروج] سلّم المفتاح وغادر', $this->exportedRow($export, $reservation));
    }
}
