<?php
namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** يحرس البنود المطبَّقة: الرأس ثلاثي الأعمدة، أرقام التواصل، وكشف الصندوق العام */
class ExportHeaderAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'hotel_name_ar'    => 'فندق أسامة',
            'hotel_name_en'    => 'Osamma Hotel',
            'hotel_address_ar' => 'شارع الستين، صنعاء',
            'hotel_address_en' => 'Sixty St., Sanaa',
            'hotel_phone'      => '01234567',
            'hotel_whatsapp'   => '0777123456',
        ] as $k => $v) {
            Setting::set($k, $v);
        }
    }

    public function test_contact_line_joins_only_the_configured_numbers(): void
    {
        $line = Setting::contactLine();

        $this->assertStringContainsString('01234567', $line);
        $this->assertStringContainsString('0777123456', $line);
        // الحقول غير المضبوطة لا تظهر كشرطة
        $this->assertStringNotContainsString('—', $line);
    }

    /** الرأس: العربية يميناً، الشعار وسطاً، الإنجليزية يساراً */
    public function test_full_export_header_renders_both_languages_and_contacts(): void
    {
        $html = view('partials.pdf-hotel-header-full')->render();

        $this->assertStringContainsString('فندق أسامة', $html);
        $this->assertStringContainsString('Osamma Hotel', $html);
        $this->assertStringContainsString('شارع الستين، صنعاء', $html);
        $this->assertStringContainsString('01234567', $html);
        $this->assertStringContainsString('واتساب', $html);
        // ثلاثة أعمدة في جدول (dompdf لا يدعم flex)
        $this->assertStringContainsString('<table', $html);
    }

    /** كشف الصندوق العام يعرض الموجود الفعلي وصناديق المستخدمين */
    public function test_general_safe_report_shows_user_safes(): void
    {
        $this->actingAs($this->admin())->get('/reports/general-safe')
            ->assertOk()
            ->assertSee('صناديق المستخدمين', false);
    }

    public function test_government_report_carries_the_hotel_contacts(): void
    {
        $html = view('partials.pdf-hotel-header-full')->render();
        $this->assertStringContainsString('01234567', $html);

        // القالب الحكومي يستعمل نفس الجزئية
        $this->assertStringContainsString(
            'pdf-hotel-header',
            file_get_contents(resource_path('views/reports/government_pdf.blade.php'))
        );
    }

    /** كشف الموظف يعرض القيم الفعلية (جوال/راتب) لا رموزاً */
    public function test_employee_statement_renders_real_values(): void
    {
        $employee = \App\Models\Employee::create([
            'name' => 'موظف كشف', 'position' => 'استقبال',
            'base_salary' => 100000, 'phone' => '0711223344',
            'hire_date' => now()->subYear(), 'is_active' => true,
        ]);

        $this->actingAs($this->admin())
            ->get("/employees/{$employee->id}/statement")
            ->assertOk()
            ->assertSee('0711223344', false)
            ->assertSee('100,000', false)
            ->assertDontSee('Â', false);
    }
}
