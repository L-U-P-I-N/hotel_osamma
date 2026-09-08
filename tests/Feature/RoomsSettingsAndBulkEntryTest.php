<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomsSettingsAndBulkEntryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    /**
     * التحديد المتعدد للغرف (حالة/سعر/حذف دفعة واحدة) أُعيد لاحقاً بطلب صريح
     * من صاحب الفندق رغم إزالته سابقاً — راجع RoomsBulkStatusTest للتغطية
     * الكاملة. هنا فقط نتأكد من وجود نقطة الدخول ومسارات الحذف الجماعي.
     */
    public function test_rooms_page_has_the_multi_select_entry_point(): void
    {
        $this->actingAs($this->admin())->get('/rooms')
            ->assertOk()
            ->assertSee('تحديد عدة غرف', false);
    }

    public function test_bulk_delete_route_exists(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('rooms.bulkDelete'));
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
