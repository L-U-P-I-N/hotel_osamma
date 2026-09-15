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

    /**
     * اسم الفندق في الترويسة يأتي من الإعدادات دائماً — لا اسم مكتوب في القالب.
     */
    public function test_header_prints_the_hotel_name_from_settings(): void
    {
        Setting::set('hotel_name_ar', 'فندق السعودي السياحي');

        $html = view('partials.pdf-hotel-header-full')->render();

        $this->assertStringContainsString('فندق السعودي السياحي', $html);
    }

    /**
     * التخطيط ثلاثي الأعمدة هو الشكل المعتمد دائماً — لا يتبدّل حين تنقص
     * الحقول الإنجليزية؛ يبقى عمودها فارغاً حتى تُملأ من شاشة الإعدادات.
     */
    public function test_header_keeps_the_three_column_layout_even_without_english(): void
    {
        Setting::set('hotel_name_ar', 'فندق السعودي السياحي');
        Setting::set('hotel_name_en', '');
        Setting::set('hotel_tagline_en', '');
        Setting::set('hotel_address_en', '');

        $html = view('partials.pdf-hotel-header-full')->render();

        $this->assertStringContainsString('width:26%', $html); // عمود الشعار الأوسط باقٍ
        $this->assertStringContainsString('width:37%', $html); // العمودان الجانبيان باقيان
        $this->assertStringContainsString('فندق السعودي السياحي', $html);
    }

    public function test_header_uses_three_columns_when_both_languages_are_configured(): void
    {
        Setting::set('hotel_name_ar', 'فندق السعودي السياحي');
        Setting::set('hotel_name_en', 'Al Saudi Tourist Hotel');

        $html = view('partials.pdf-hotel-header-full')->render();

        $this->assertStringContainsString('Al Saudi Tourist Hotel', $html);
        $this->assertStringContainsString('فندق السعودي السياحي', $html);
        $this->assertStringContainsString('width:26%', $html); // عمود الشعار الأوسط
    }
}
