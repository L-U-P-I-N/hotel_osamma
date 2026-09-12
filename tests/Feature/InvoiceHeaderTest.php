<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * فاتورة الحجز (كاملة وجزئية) يجب أن تستخدم نفس الترويسة الرسمية الموحَّدة
 * (عربي يمين / الشعار وسطاً / إنجليزي يسار، بإطار مستطيل) المستخدمة في كل
 * تقارير الفندق الأخرى — لا رأساً مختلفاً خاصاً بها.
 */
class InvoiceHeaderTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function checkedInGuest(): Reservation
    {
        $room = Room::where('status', 'available')->firstOrFail();
        return Reservation::create([
            'guest_id'       => Guest::firstOrFail()->id,
            'room_id'        => $room->id,
            'created_by'     => $this->admin()->id,
            'check_in_date'  => today(),
            'check_out_date' => today()->addDays(2),
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => 40000,
        ]);
    }

    public function test_full_invoice_renders_the_shared_official_header(): void
    {
        Setting::set('hotel_name_en', 'Osamma Hotel');
        $r = $this->checkedInGuest();

        $response = $this->actingAs($this->admin())->get("/reservations/{$r->id}/invoice");

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_invoice_blade_includes_the_shared_header_partial(): void
    {
        $this->assertStringContainsString(
            "@include('partials.pdf-hotel-header-full'",
            file_get_contents(resource_path('views/reservations/invoice.blade.php'))
        );
        $this->assertStringContainsString(
            "@include('partials.pdf-hotel-header-full'",
            file_get_contents(resource_path('views/reservations/invoice-partial.blade.php'))
        );
    }

    public function test_shared_header_wraps_in_a_bordered_letterhead_rectangle(): void
    {
        $html = file_get_contents(resource_path('views/partials/pdf-hotel-header-full.blade.php'));
        // إطار حول الترويسة بلون الهوية — العرض بالبكسل تفصيل تصميمي قابل للتغيير
        $this->assertMatchesRegularExpression('/border:\s*[\d.]+px solid #0F4C75/', $html);
    }
}
