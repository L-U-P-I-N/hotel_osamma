<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * الصندوق العام قسم مستقل ببحثه الفوري داخل الشاشة نفسها (كصفحة الحجوزات)،
 * لا زرّاً ينقل لصفحة أخرى. وكشف حساب النزيل يعرض بياناته مرتَّبة
 * "تسمية: قيمة" ورقم الغرفة أولاً.
 */
class GeneralSafeSearchTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function guestInRoom(string $name): Guest
    {
        $guest = Guest::create([
            'full_name' => $name, 'nationality' => 'يمني', 'id_type' => 'national_id',
            'id_number' => '01' . random_int(1000000, 9999999),
        ]);

        Reservation::create([
            'guest_id'       => $guest->id,
            'room_id'        => Room::where('status', 'available')->firstOrFail()->id,
            'created_by'     => $this->admin()->id,
            'check_in_date'  => today()->subDay(),
            'check_out_date' => today()->addDay(),
            'check_in_time'  => '14:00',
            'check_out_time' => '13:00',
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => 20000,
        ]);

        return $guest;
    }

    public function test_general_safe_page_searches_people_inline(): void
    {
        $guest = $this->guestInRoom('سالم أحمد صالح');

        $this->actingAs($this->admin())
            ->get(route('reports.generalSafe', ['q' => 'سالم']))
            ->assertOk()
            ->assertSee($guest->full_name)
            ->assertSee(route('guests.statement', $guest), false);
    }

    public function test_general_safe_page_keeps_the_period_while_searching(): void
    {
        $this->actingAs($this->admin())
            ->get(route('reports.generalSafe', ['q' => 'لا-أحد-بهذا-الاسم', 'from' => '2026-01-01', 'to' => '2026-01-31']))
            ->assertOk()
            ->assertSee('لا توجد نتائج');
    }

    public function test_general_safe_no_longer_links_out_to_a_separate_search_page(): void
    {
        $html = file_get_contents(resource_path('views/reports/general-safe.blade.php'));

        $this->assertStringNotContainsString("route('reports.accountSearch')", $html);
        $this->assertStringContainsString('account-search-box', $html);
    }

    public function test_guest_statement_pdf_renders_with_the_ordered_info_block(): void
    {
        $guest = $this->guestInRoom('سالم أحمد صالح');

        $response = $this->actingAs($this->admin())->get(route('guests.statement.pdf', $guest));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));

        // الغرفة أول بيان في الكشف، والبيانات في شبكة تسمية/قيمة لا سطر واحد
        $blade = file_get_contents(resource_path('views/guests/statement_pdf.blade.php'));
        $this->assertStringContainsString('رقم الغرفة', $blade);
        $this->assertStringContainsString('info-grid', $blade);
        $this->assertLessThan(
            mb_strpos($blade, 'الجنسية'),
            mb_strpos($blade, 'رقم الغرفة'),
            'رقم الغرفة يجب أن يسبق باقي البيانات'
        );
    }
}
