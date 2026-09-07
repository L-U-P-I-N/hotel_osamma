<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحة تسجيل الدخول تعرض حقل المرافق بإمكانية بحث عن نزيل عائد أيضاً (قد يكون
 * المرافق نفسه نزيلاً مسجَّلاً)، وتستخدم نفس نقطة نهاية بحث النزلاء الحالية.
 */
class CheckInCompanionAutofillTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    public function test_checkin_page_renders_with_companion_search_wiring(): void
    {
        $response = $this->actingAs($this->admin())->get('/checkin');

        $response->assertOk();
        $response->assertSee('searchCompanionGuests', false);
        $response->assertSee('selectCompanionGuest', false);
        $response->assertSee('_companionsSource', false);
    }

    public function test_guest_search_endpoint_is_reusable_for_companion_lookup(): void
    {
        Guest::create(['full_name' => 'أحمد المرافق', 'nationality' => 'يمني', 'phone' => '777000111']);

        $response = $this->actingAs($this->admin())
            ->get('/guests/search?q=' . urlencode('أحمد المرافق'));

        $response->assertOk();
        $response->assertJsonFragment(['full_name' => 'أحمد المرافق']);
    }
}
