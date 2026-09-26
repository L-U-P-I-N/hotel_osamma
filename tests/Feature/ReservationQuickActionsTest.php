<?php
namespace Tests\Feature;

use App\Models\ExtraCharge;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\ReservationNote;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * العمل السريع من جدول الحجوزات: تجديد ورسوم وملاحظات دون مغادرة الصفحة —
 * الموظف يُنهي عدة نزلاء تباعاً بلا انتظار تحميل صفحة بين كل واحد والذي يليه.
 */
class ReservationQuickActionsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function stay(): Reservation
    {
        $admin = $this->admin();

        Shift::firstOrCreate(
            ['user_id' => $admin->id, 'shift_date' => today(), 'is_closed' => false],
            ['started_at' => now()->subHour(), 'opening_balance_yer' => 0]
        );

        $guest = Guest::create([
            'full_name' => 'نزيل الجدول', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '04' . random_int(1000000, 9999999),
        ]);

        return Reservation::create([
            'guest_id' => $guest->id,
            'room_id'  => Room::where('status', 'available')->firstOrFail()->id,
            'created_by' => $admin->id,
            'check_in_date' => today()->subDay(), 'check_out_date' => today()->addDay(),
            'check_in_time' => '14:00', 'check_out_time' => '13:00',
            'status' => 'checked_in', 'payment_status' => 'unpaid',
            'total_amount' => 40000, 'first_night_price' => 20000, 'renewal_price_per_night' => 20000,
        ]);
    }

    /* ═══ تجديد دون مغادرة الصفحة ═══ */

    public function test_renewing_from_the_table_returns_the_updated_row_as_json(): void
    {
        $reservation = $this->stay();

        $response = $this->actingAs($this->admin())
            ->postJson(route('reservations.renew', $reservation), [
                'new_check_out_date' => today()->addDays(3)->toDateString(),
                'renewal_price'      => 20000,
                'advance_payment'    => 0,
                'payment_method'     => 'cash',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('reservation.id', $reservation->id)
            ->assertJsonPath('reservation.check_out_date_raw', today()->addDays(3)->toDateString())
            ->assertJsonPath('reservation.total_amount', '80,000');

        $this->assertStringContainsString('تم تجديد الإقامة بنجاح', $response->json('message'));
    }

    public function test_a_failed_renewal_answers_with_json_too(): void
    {
        $reservation = $this->stay();

        // تاريخ خروج قبل الحالي — يجب أن يُرفض دون إعادة توجيه لصفحة أخرى
        $this->actingAs($this->admin())
            ->postJson(route('reservations.renew', $reservation), [
                'new_check_out_date' => today()->subDays(5)->toDateString(),
                'renewal_price'      => 20000,
            ])
            ->assertStatus(422);
    }

    /* ═══ رسم على الغرفة ═══ */

    public function test_a_hotel_charge_is_added_to_the_room_total(): void
    {
        $reservation = $this->stay();

        $response = $this->actingAs($this->admin())
            ->postJson(route('reservations.addHotelCharge', $reservation), [
                'charge_type' => 'late_checkout',
                'amount'      => 5000,
                'description' => 'تأخّر المغادرة إلى 6 مساءً',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('reservation.total_amount', '45,000');

        $charge = ExtraCharge::firstOrFail();

        $this->assertTrue((bool) $charge->in_hotel_total, 'الرسم يجب أن يدخل إجمالي الفندق');
        $this->assertSame(45000.0, (float) $reservation->fresh()->total_amount);
        $this->assertSame(5000.0, $reservation->fresh()->hotel_charges_total);
    }

    /** بند مستقل في الفاتورة وفي إيرادات الفندق — لا مبلغ مبهم داخل السعر. */
    public function test_the_charge_shows_as_its_own_line_and_is_editable(): void
    {
        $reservation = $this->stay();

        $this->actingAs($this->admin())->postJson(route('reservations.addHotelCharge', $reservation), [
            'charge_type' => 'extra_service', 'amount' => 3000, 'description' => 'نقل من المطار',
        ])->assertOk();

        $charge = ExtraCharge::firstOrFail();
        $this->assertSame('خدمة إضافية', $charge->type_label);

        // تعديل المبلغ يُصحّح الإجمالي بالفارق
        $this->actingAs($this->admin())->put(route('reservations.updateCharge', $charge), [
            'charge_type' => 'extra_service', 'amount' => 4500, 'description' => 'نقل من المطار',
        ]);
        $this->assertSame(44500.0, (float) $reservation->fresh()->total_amount);

        // والحذف يعكس أثره بالكامل
        $this->actingAs($this->admin())->delete(route('reservations.deleteCharge', $charge));
        $this->assertSame(40000.0, (float) $reservation->fresh()->total_amount);
    }

    public function test_purchases_stay_out_of_the_room_total(): void
    {
        $reservation = $this->stay();

        // رسم المشتريات (بقالة) القديم يبقى دَيناً منفصلاً لا يمسّ الإجمالي
        $this->actingAs($this->admin())->post(route('reservations.addCharge', $reservation), [
            'charge_type' => 'grocery', 'amount' => 2000,
        ]);

        $this->assertSame(40000.0, (float) $reservation->fresh()->total_amount);
    }

    /* ═══ الملاحظات الفورية ═══ */

    public function test_notes_are_created_read_and_colour_the_icon(): void
    {
        $reservation = $this->stay();

        $response = $this->actingAs($this->admin())
            ->postJson(route('reservations.notes.store', $reservation), [
                'type' => 'obligation', 'body' => 'على النزيل تسليم مفتاح الملحق قبل المغادرة',
            ]);

        $response->assertOk()
            ->assertJsonPath('notes.open_count', 1)
            ->assertJsonPath('notes.color', 'red')
            ->assertJsonPath('notes.items.0.type_label', 'التزام قبل المغادرة');

        $this->assertStringContainsString('مفتاح الملحق', $response->json('notes.preview'));

        $this->actingAs($this->admin())
            ->getJson(route('reservations.notes.index', $reservation))
            ->assertOk()
            ->assertJsonPath('notes.open_count', 1);
    }

    public function test_the_icon_colour_follows_the_most_urgent_open_note(): void
    {
        $reservation = $this->stay();
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('reservations.notes.store', $reservation),
            ['type' => 'maintenance', 'body' => 'مكيّف الغرفة يحتاج صيانة'])
            ->assertJsonPath('notes.color', 'amber');

        // ملاحظة أشدّ تُرقّي اللون
        $this->actingAs($admin)->postJson(route('reservations.notes.store', $reservation),
            ['type' => 'payment', 'body' => 'رسوم معلّقة'])
            ->assertJsonPath('notes.color', 'red');
    }

    public function test_resolving_a_note_keeps_it_but_clears_the_alert(): void
    {
        $reservation = $this->stay();
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('reservations.notes.store', $reservation),
            ['type' => 'obligation', 'body' => 'التزام']);
        $note = ReservationNote::firstOrFail();

        $this->actingAs($admin)
            ->putJson(route('reservations.notes.update', [$reservation, $note]), ['resolved' => 1])
            ->assertOk()
            ->assertJsonPath('notes.open_count', 0)
            ->assertJsonPath('notes.color', 'none');

        // الملاحظة نفسها تبقى في السجل للمراجعة
        $this->assertCount(1, $reservation->fresh()->quickNotes);
        $this->assertNotNull($note->fresh()->resolved_at);
    }

    public function test_notes_can_be_edited_and_deleted(): void
    {
        $reservation = $this->stay();
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('reservations.notes.store', $reservation),
            ['type' => 'general', 'body' => 'نصّ قديم']);
        $note = ReservationNote::firstOrFail();

        $this->actingAs($admin)->putJson(route('reservations.notes.update', [$reservation, $note]),
            ['body' => 'نصّ محدَّث'])->assertOk();
        $this->assertSame('نصّ محدَّث', $note->fresh()->body);

        $this->actingAs($admin)->deleteJson(route('reservations.notes.destroy', [$reservation, $note]))
            ->assertOk()
            ->assertJsonPath('notes.open_count', 0);
    }

    public function test_a_note_belonging_to_another_reservation_is_rejected(): void
    {
        $first  = $this->stay();
        $second = $this->stay();

        $this->actingAs($this->admin())->postJson(route('reservations.notes.store', $first),
            ['type' => 'general', 'body' => 'ملاحظة']);
        $note = ReservationNote::firstOrFail();

        $this->actingAs($this->admin())
            ->deleteJson(route('reservations.notes.destroy', [$second, $note]))
            ->assertNotFound();
    }

    /**
     * الصفحة الحيّة هي جدول الإقامات (/reservations يعيد التوجيه إليها)،
     * وفيها تظهر أيقونة الملاحظات وأزرار العمل السريع.
     */
    public function test_the_live_table_shows_the_notes_icon_and_the_quick_buttons(): void
    {
        $reservation = $this->stay();

        $this->actingAs($this->admin())->postJson(route('reservations.notes.store', $reservation),
            ['type' => 'obligation', 'body' => 'التزام قبل المغادرة']);

        $page = $this->actingAs($this->admin())->get(route('reservations.expiring'))->assertOk();

        $page->assertSee('data-notes-btn="' . $reservation->id . '"', false);
        $page->assertSee('openCharge(' . $reservation->id, false);
        $page->assertSee('data-inline-renew="' . $reservation->id . '"', false);
        // معاينة الملاحظة تظهر في تلميح الأيقونة دون فتح أي نافذة
        $page->assertSee('التزام قبل المغادرة');
    }

    /**
     * الملاحظة المكتوبة مع الرسم هي مبرِّر الزيادة — فتُطبع في الفاتورة
     * وتُعرض في تفاصيل الحجز بدل مبلغ مبهم مدموج في الإجمالي.
     */
    public function test_the_charge_note_is_itemised_in_the_invoice_and_the_details_page(): void
    {
        $reservation = $this->stay();

        $this->actingAs($this->admin())->postJson(route('reservations.addHotelCharge', $reservation), [
            'charge_type' => 'late_checkout', 'amount' => 5000, 'description' => 'تأخّر المغادرة إلى 6 مساءً',
        ])->assertOk();

        $details = $this->actingAs($this->admin())->get(route('reservations.show', $reservation))->assertOk();
        $details->assertSee('رسوم إضافية على الغرفة');
        $details->assertSee('تأخير عن موعد المغادرة');
        $details->assertSee('تأخّر المغادرة إلى 6 مساءً');

        // الفاتورة تُصدَّر PDF (نصّها مُشكَّل للعرض)، فنفحص قالبها قبل التحويل
        $invoiceHtml = view('reservations.invoice', ['reservation' => $reservation->fresh()])->render();
        $this->assertStringContainsString('رسوم إضافية على الغرفة', $invoiceHtml);
        $this->assertStringContainsString('تأخّر المغادرة إلى 6 مساءً', $invoiceHtml);

        // وتُصدَّر فعلاً دون خطأ
        $this->actingAs($this->admin())->get(route('reservations.invoice', $reservation))->assertOk();
    }

    /** الرسم يدخل الإيراد عند تحصيله — لا قبل ذلك، فلا يُضخَّم الإيراد بمبلغ لم يُقبض. */
    public function test_the_charge_reaches_revenue_only_once_it_is_collected(): void
    {
        $reservation = $this->stay();
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('reservations.addHotelCharge', $reservation), [
            'charge_type' => 'late_checkout', 'amount' => 5000,
        ])->assertOk();

        $this->assertSame(0.0, (float) \App\Models\Payment::where('reservation_id', $reservation->id)->sum('amount'));

        $this->actingAs($admin)->post(route('payments.store'), [
            'reservation_id' => $reservation->id,
            'amount'         => 5000,
            'method'         => 'cash',
            'type'           => 'partial',
        ]);

        $this->assertSame(5000.0, (float) \App\Models\Payment::where('reservation_id', $reservation->id)->sum('amount'));
    }

    public function test_reservations_url_lands_on_the_live_table(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reservations.index'))
            ->assertRedirect(route('reservations.expiring'));
    }
}
