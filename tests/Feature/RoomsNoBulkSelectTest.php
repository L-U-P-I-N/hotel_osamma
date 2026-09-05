<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomsNoBulkSelectTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    /** أُلغي تحديد الغرف المتعدد لأنه كان مصدر أخطاء */
    public function test_rooms_page_has_no_multi_select_or_bulk_delete(): void
    {
        $this->actingAs($this->admin())->get('/rooms')
            ->assertOk()
            ->assertDontSee('تحديد متعدد', false)
            ->assertDontSee('حذف المحدد', false)
            ->assertDontSee('تحديد الكل', false)
            ->assertDontSee('selectMode', false);
    }

    public function test_bulk_delete_route_no_longer_exists(): void
    {
        $this->assertFalse(
            \Illuminate\Support\Facades\Route::has('rooms.bulkDelete'),
            'مسار الحذف الجماعي يجب أن يكون قد أُزيل'
        );
    }

    public function test_settings_page_exposes_the_hotel_profile_fields(): void
    {
        $this->actingAs($this->admin())->get('/settings')
            ->assertOk()
            ->assertSee('hotel_name_ar', false)
            ->assertSee('hotel_phone', false)
            ->assertSee('hotel_address_ar', false)
            ->assertSee('hotel_currency', false)
            ->assertSee('hotel_footer_note', false);
    }
}
