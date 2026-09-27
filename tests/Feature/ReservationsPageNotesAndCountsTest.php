<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\HotelNote;
use App\Models\Reservation;
use App\Models\ReservationNote;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحة الحجوزات: قراءة الملاحظات ومعرفة من في الفندق الآن.
 *
 * كانت الملاحظة مخفيّة خلف أيقونة تحتاج فتح نافذة لكل صفّ، وكان "الإجمالي"
 * وحده معروضاً وهو يضمّ المغادرين تاريخياً فلا يُقرأ منه عدد النزلاء الحاضرين.
 */
class ReservationsPageNotesAndCountsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    /**
     * البذور تُنشئ حجوزات عرضٍ توضيحية، والعدّادات تُقاس على أرقام معلومة —
     * فنبدأ من قائمة نظيفة بدل أن نطارد أرقام البذور كلما تغيّرت.
     */
    private function clearStays(): void
    {
        Reservation::query()->withTrashed()->forceDelete();
    }

    private function stay(string $name, string $status = 'checked_in', $checkOut = null): Reservation
    {
        $guest = Guest::create([
            'full_name' => $name, 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '04' . random_int(1000000, 9999999),
        ]);

        return Reservation::create([
            'guest_id'   => $guest->id,
            'room_id'    => Room::where('status', 'available')->skip(Reservation::count())->firstOrFail()->id,
            'created_by' => $this->admin()->id,
            'check_in_date'  => today()->subDay(),
            'check_out_date' => $checkOut ?? today()->addDays(2),
            'status' => $status, 'payment_status' => 'paid',
            'total_amount' => 20000, 'paid_amount' => 20000,
            'actual_check_out' => $status === 'checked_out' ? now() : null,
        ]);
    }

    private function page(): string
    {
        return $this->actingAs($this->admin())
            ->get(route('reservations.expiring'))
            ->assertOk()
            ->getContent();
    }

    /* ═══ نصّ الملاحظة ظاهر في الجدول ═══ */

    public function test_the_note_text_is_shown_next_to_its_icon(): void
    {
        $reservation = $this->stay('نزيل بملاحظة');
        ReservationNote::create([
            'reservation_id' => $reservation->id,
            'type' => 'obligation',
            'body' => 'على النزيل تسليم المفتاح قبل المغادرة',
            'created_by' => $this->admin()->id,
        ]);

        $html = $this->page();

        $this->assertStringContainsString('على النزيل تسليم المفتاح قبل المغادرة', $html);
        $this->assertStringContainsString('data-note-text="' . $reservation->id . '"', $html);
        // الأيقونة تبقى موجودة كما كانت
        $this->assertStringContainsString('data-notes-btn="' . $reservation->id . '"', $html);
    }

    /** أشدّ ملاحظة قائمة هي التي يُعرض نصّها، لا أحدثها فقط. */
    public function test_the_most_severe_open_note_is_the_one_shown(): void
    {
        $reservation = $this->stay('نزيل بملاحظتين');

        ReservationNote::create([
            'reservation_id' => $reservation->id, 'type' => 'payment',
            'body' => 'رسوم معلّقة على الغرفة', 'created_by' => $this->admin()->id,
        ]);
        ReservationNote::create([
            'reservation_id' => $reservation->id, 'type' => 'general',
            'body' => 'طلب وسادة إضافية', 'created_by' => $this->admin()->id,
        ]);

        $html = $this->page();

        $this->assertStringContainsString('رسوم معلّقة على الغرفة', $html);
        $this->assertStringContainsString('+1 ملاحظة أخرى', $html);
    }

    public function test_the_page_offers_a_switch_to_hide_the_note_texts(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('data-notes-text-toggle', $html);
        $this->assertStringContainsString('toggleNotesText()', $html);
        $this->assertStringContainsString('إخفاء نصوص الملاحظات', $html);
        // الاختيار محفوظ في المتصفح فيبقى بين الصفحات
        $this->assertStringContainsString('resNotesTextHidden', $html);
    }

    /* ═══ الملاحظات العامة للفندق ═══ */

    public function test_the_general_notes_board_sits_at_the_top_of_the_page(): void
    {
        HotelNote::create([
            'type' => 'urgent', 'body' => 'المصعد معطّل — استخدم السلّم',
            'created_by' => $this->admin()->id,
        ]);

        $html = $this->page();

        $this->assertStringContainsString('ملاحظات عامة للفندق', $html);
        $this->assertStringContainsString('المصعد معطّل — استخدم السلّم', $html);
        // اللوحة قبل جدول النزلاء
        $this->assertLessThan(
            strpos($html, 'id="resultsArea"'),
            strpos($html, 'ملاحظات عامة للفندق')
        );
    }

    public function test_a_general_note_is_added_read_and_resolved_over_json(): void
    {
        $admin = $this->admin();

        $created = $this->actingAs($admin)
            ->postJson(route('hotel-notes.store'), [
                'type' => 'handover', 'body' => 'سلّم مفتاح الغرفة 201 لوردية الليل', 'is_pinned' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('notes.open_count', 1);

        $note = HotelNote::firstOrFail();
        $this->assertTrue($note->is_pinned);
        $this->assertSame('سلّم مفتاح الغرفة 201 لوردية الليل', $created->json('notes.items.0.body'));

        $this->actingAs($admin)->getJson(route('hotel-notes.index'))
            ->assertOk()->assertJsonPath('notes.open_count', 1);

        $this->actingAs($admin)
            ->putJson(route('hotel-notes.update', $note), ['resolved' => 1])
            ->assertOk()
            ->assertJsonPath('notes.open_count', 0);

        $this->assertNotNull($note->refresh()->resolved_at);
        $this->assertSame($admin->id, $note->resolved_by);
    }

    public function test_a_general_note_is_deleted_and_validated(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('hotel-notes.store'), ['type' => 'general', 'body' => ''])
            ->assertStatus(422);
        $this->actingAs($admin)->postJson(route('hotel-notes.store'), ['type' => 'unknown', 'body' => 'نصّ'])
            ->assertStatus(422);

        $this->actingAs($admin)->postJson(route('hotel-notes.store'), ['type' => 'general', 'body' => 'ملاحظة للحذف']);
        $note = HotelNote::firstOrFail();

        $this->actingAs($admin)->deleteJson(route('hotel-notes.destroy', $note))
            ->assertOk()->assertJsonPath('notes.open_count', 0);

        $this->assertSoftDeleted($note);
    }

    /** المثبّتة أعلى القائمة وإن كُتبت ملاحظات أحدث بعدها. */
    public function test_a_pinned_note_leads_the_board(): void
    {
        $admin = $this->admin();
        HotelNote::create(['type' => 'general', 'body' => 'ملاحظة مثبّتة', 'is_pinned' => true, 'created_by' => $admin->id]);
        HotelNote::create(['type' => 'general', 'body' => 'ملاحظة أحدث', 'created_by' => $admin->id]);

        $items = $this->actingAs($admin)->getJson(route('hotel-notes.index'))->json('notes.items');

        $this->assertSame('ملاحظة مثبّتة', $items[0]['body']);
    }

    /* ═══ عدّادات النزلاء ═══ */

    public function test_the_page_counts_the_guests_present_right_now(): void
    {
        $this->clearStays();
        $this->stay('مقيم أول');
        $this->stay('مقيم ثانٍ', 'checked_in', today());   // خروجه اليوم
        $this->stay('مغادر', 'checked_out');

        $html = $this->page();

        $this->assertStringContainsString('النزلاء الموجودون الآن', $html);
        $this->assertStringContainsString('إجمالي النزلاء (الكل)', $html);
        $this->assertStringContainsString('خروجهم اليوم', $html);
        $this->assertStringContainsString('data-present="2"', $html);
        $this->assertStringContainsString('data-total="3"', $html);
        $this->assertStringContainsString('data-today="1"', $html);
        $this->assertStringContainsString('data-departed="1"', $html);
    }

    /** العدّادات تُحسب على كل النتائج لا على الصفحة المعروضة وحدها. */
    public function test_the_counts_follow_the_status_filter(): void
    {
        $this->clearStays();
        $this->stay('مقيم');
        $this->stay('مغادر', 'checked_out');

        $html = $this->actingAs($this->admin())
            ->get(route('reservations.expiring', ['status' => 'checked_in']))
            ->assertOk()->getContent();

        $this->assertStringContainsString('data-present="1"', $html);
        $this->assertStringContainsString('data-total="1"', $html);
    }

    /* ═══ تسمية الرسوم الإضافية ═══ */

    public function test_the_room_charge_button_is_named_extra_charges(): void
    {
        $this->stay('نزيل للرسوم');

        $html = $this->page();

        $this->assertStringContainsString('رسوم إضافية', $html);
        // النافذة نفسها تحمل التسمية الجديدة
        $this->assertStringContainsString('إضافة رسوم إضافية على الغرفة', $html);
        $this->assertStringContainsString('نوع الرسوم الإضافية', $html);
        // لم يبقَ زرّ باسم «رسم» مفرداً
        $this->assertStringNotContainsString('>رسم<', $html);
    }
}
