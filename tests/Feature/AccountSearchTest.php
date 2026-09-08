<?php
namespace Tests\Feature;

use App\Models\Companion;
use App\Models\Employee;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * كشف حسابات موحّد: بحث بالاسم عن أي نزيل/مرافق/موظف/مستخدم من شاشة واحدة،
 * وكل نتيجة تربط بكشف حسابه الكامل (حجوزات وفواتير، أو راتب وسحوبات، أو
 * حركة صندوقه). كل فئة نتائج مقصورة على من يملك صلاحية الاطّلاع عليها.
 */
class AccountSearchTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function reservationWithCompanion(string $guestName, string $companionName): Reservation
    {
        $room = Room::where('status', 'available')->firstOrFail();
        $guest = Guest::create(['full_name' => $guestName, 'nationality' => 'يمني']);

        $r = Reservation::create([
            'guest_id' => $guest->id, 'room_id' => $room->id,
            'created_by' => $this->admin()->id,
            'check_in_date' => today(), 'check_out_date' => today()->addDays(2),
            'status' => 'checked_in', 'payment_status' => 'unpaid', 'total_amount' => 20000,
        ]);

        Companion::create([
            'reservation_id' => $r->id, 'full_name' => $companionName,
            'nationality' => 'يمني', 'relationship' => 'wife',
        ]);

        return $r;
    }

    public function test_search_finds_a_guest_and_links_to_the_statement(): void
    {
        $this->reservationWithCompanion('أحمد محمد الكشفي', 'مرافقة أحمد');

        $response = $this->actingAs($this->admin())
            ->get('/reports/account-search?q=' . urlencode('الكشفي'));

        $response->assertOk()->assertSee('أحمد محمد الكشفي', false);
        $guest = Guest::where('full_name', 'أحمد محمد الكشفي')->firstOrFail();
        $response->assertSee(route('guests.statement', $guest), false);
    }

    public function test_search_finds_a_companion_and_links_to_the_main_guests_statement(): void
    {
        $r = $this->reservationWithCompanion('نزيل رئيسي واحد', 'مرافقة فريدة للبحث');

        $response = $this->actingAs($this->admin())
            ->get('/reports/account-search?q=' . urlencode('فريدة للبحث'));

        $response->assertOk()->assertSee('مرافقة فريدة للبحث', false);
        $response->assertSee(route('guests.statement', $r->guest), false);
    }

    public function test_search_finds_an_employee(): void
    {
        Employee::create([
            'name' => 'موظف بحث فريد', 'position' => 'استقبال',
            'base_salary' => 100000, 'hire_date' => now()->subYear(), 'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin())
            ->get('/reports/account-search?q=' . urlencode('بحث فريد'));

        $response->assertOk()->assertSee('موظف بحث فريد', false);
    }

    /**
     * التفويض الفعلي في هذا المشروع عبر PermissionService (سجل صلاحيات لكل
     * مستخدم) لا صلاحيات Spatie للأدوار — راجع Gate::before في
     * AppServiceProvider. موظف استقبال عادي مُنِح accounts.view صراحةً لكن
     * ليس hr.view (غير موجود في RECEPTIONIST_DEFAULTS) يجب ألا يرى الموظفين.
     */
    public function test_employee_results_are_hidden_without_hr_permission(): void
    {
        Employee::create([
            'name' => 'موظف محجوب عني', 'position' => 'استقبال',
            'base_salary' => 100000, 'hire_date' => now()->subYear(), 'is_active' => true,
        ]);

        $receptionist = User::role('receptionist')->firstOrFail();
        PermissionService::toggle($receptionist, 'reports.view', true, $this->admin());
        PermissionService::toggle($receptionist, 'accounts.view', true, $this->admin());

        $response = $this->actingAs($receptionist)
            ->get('/reports/account-search?q=' . urlencode('محجوب'));

        $response->assertOk()->assertDontSee('موظف محجوب عني', false);
    }

    public function test_a_short_query_asks_for_more_characters_without_erroring(): void
    {
        $this->actingAs($this->admin())
            ->get('/reports/account-search?q=' . urlencode('a'))
            ->assertOk()
            ->assertSee('حرفين على الأقل', false);
    }
}
