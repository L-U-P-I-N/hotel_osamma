<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تقرير عم علي: نزيل حاجز جناحاً كاملاً (A+B) يُسجَّل حجزه بغرفة واحدة فقط
 * (room_id) والقسم الآخر مرتبط عبر linked_room_id — بدون مطابقة linked_room_id
 * في الاستعلام كان قسم الجناح غير الأساسي يظهر "مشغول" بلا أي بيانات نزيل.
 */
class AmAliReportSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function bookFullSuite(): array
    {
        $a = Room::where('room_number', '201A')->firstOrFail();
        $b = Room::where('room_number', '201B')->firstOrFail();

        $r = Reservation::create([
            'guest_id'           => Guest::firstOrFail()->id,
            'room_id'            => $a->id,
            'linked_room_id'     => $b->id,
            'suite_booking_type' => 'both',
            'created_by'         => $this->admin()->id,
            'check_in_date'      => today(),
            'check_out_date'     => today()->addDays(2),
            'status'             => 'checked_in',
            'payment_status'     => 'unpaid',
            'total_amount'       => 100000,
        ]);
        $a->update(['status' => 'occupied']);
        $b->update(['status' => 'occupied']);

        return [$r, $a, $b];
    }

    public function test_the_non_primary_suite_section_shows_the_same_guest_data(): void
    {
        [$r, $a, $b] = $this->bookFullSuite();

        $response = $this->actingAs($this->admin())->get('/reports/am-ali?date=' . today()->toDateString());
        $response->assertOk();

        $rows = $response->viewData('rows');
        $rowB = $rows->firstWhere(fn($row) => $row['room']->id === $b->id);

        $this->assertNotNull($rowB['today'] ?? null, 'قسم الجناح B يجب أن يظهر بيانات نفس النزيل');
        $this->assertSame($r->id, $rowB['today']['reservation_id']);
    }

    public function test_suite_sections_are_merged_into_one_row_via_rowspan(): void
    {
        [$r, $a, $b] = $this->bookFullSuite();

        $response = $this->actingAs($this->admin())
            ->get('/reports/am-ali?date=' . today()->toDateString());
        $rows = $response->viewData('rows')->values();

        $indexA = $rows->search(fn($row) => $row['room']->id === $a->id);
        $indexB = $rows->search(fn($row) => $row['room']->id === $b->id);

        $this->assertSame(2, $rows[$indexA]['rowspan'] ?? null, 'قسم A يحمل rowspan=2');
        $this->assertTrue($rows[$indexB]['merged'] ?? false, 'قسم B يُعتبر مدموجاً');
    }

    public function test_pdf_export_renders_without_error_for_full_suite_booking(): void
    {
        $this->bookFullSuite();

        $response = $this->actingAs($this->admin())
            ->get('/reports/am-ali/pdf?date=' . today()->toDateString());

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_report_includes_the_key_location_column(): void
    {
        $response = $this->actingAs($this->admin())->get('/reports/am-ali');
        $response->assertOk()->assertSee('أين القفل', false);
    }
}
