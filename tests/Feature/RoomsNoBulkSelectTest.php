<?php
namespace Tests\Feature;

use App\Models\Room;
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

    /** توحيد السعر بالنوع ما زال يعمل — وهو آمن لأنه لا يعتمد على تحديد يدوي */
    public function test_unifying_prices_by_room_type_still_works(): void
    {
        $room = Room::where('room_sub_type', 'regular')->firstOrFail();

        $this->actingAs($this->admin())->post('/rooms/bulk-price', [
            'sub_type'  => 'regular',
            'price_yer' => 12345,
        ])->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(12345, (float) $room->fresh()->price_yer, 0.01);
    }

    /** تمرير معرّفات غرف لم يعد يخصّص التحديث — يُطبَّق بالنوع فقط */
    public function test_passing_room_ids_no_longer_narrows_the_update(): void
    {
        $regular = Room::where('room_sub_type', 'regular')->take(2)->get();
        $this->assertCount(2, $regular);

        $this->actingAs($this->admin())->post('/rooms/bulk-price', [
            'sub_type'   => 'regular',
            'room_ids'   => [$regular->first()->id],
            'price_yer'  => 7777,
        ])->assertSessionHasNoErrors();

        // كلتاهما تحدّثتا لأن التطبيق بالنوع لا بالتحديد
        foreach ($regular as $room) {
            $this->assertEqualsWithDelta(7777, (float) $room->fresh()->price_yer, 0.01);
        }
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
