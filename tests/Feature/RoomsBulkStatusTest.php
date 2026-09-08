<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تحديد عدة غرف من صفحة الغرف دفعة واحدة: تغيير حالتها (متاحة/تحت الفحص/صيانة)،
 * تعديل سعرها، أو حذفها — الثلاثة على غرف يحدّدها الموظف بنفسه صراحةً (بخلاف
 * "توحيد سعر النوع" القديمة التي كانت تُغيّر غرفاً لم يُقصَد أصلاً).
 */
class RoomsBulkStatusTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    public function test_all_three_bulk_routes_exist(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('rooms.bulkStatus'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('rooms.bulkPrice'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('rooms.bulkDelete'));
    }

    public function test_admin_can_set_the_price_of_multiple_rooms_at_once(): void
    {
        $rooms = Room::where('status', 'available')->limit(3)->get();

        $response = $this->actingAs($this->admin())
            ->postJson('/rooms/bulk-price', [
                'room_ids'  => $rooms->pluck('id')->all(),
                'price_yer' => 55000,
            ]);

        $response->assertOk()->assertJson(['success' => true, 'updated' => 3]);
        foreach ($rooms as $room) {
            $this->assertEqualsWithDelta(55000, (float) $room->fresh()->price_yer, 0.01);
        }
    }

    public function test_admin_can_delete_multiple_rooms_at_once(): void
    {
        $rooms = Room::where('status', 'available')->limit(2)->get();
        $ids = $rooms->pluck('id')->all();

        $response = $this->actingAs($this->admin())
            ->postJson('/rooms/bulk-delete', ['room_ids' => $ids]);

        $response->assertOk()->assertJson(['success' => true, 'deleted' => 2]);
        foreach ($ids as $id) {
            $this->assertNull(Room::find($id));
        }
    }

    /** حذف جماعي يستثني غرفة بها نزيل فعلي بدل رفض الدفعة كلها */
    public function test_bulk_delete_skips_occupied_rooms(): void
    {
        $occupied = Room::where('status', 'available')->first();
        $free     = Room::where('status', 'available')->where('id', '!=', $occupied->id)->first();

        Reservation::create([
            'guest_id' => Guest::firstOrFail()->id, 'room_id' => $occupied->id,
            'created_by' => $this->admin()->id,
            'check_in_date' => today(), 'check_out_date' => today()->addDays(2),
            'status' => 'checked_in', 'payment_status' => 'unpaid', 'total_amount' => 1000,
        ]);
        $occupied->update(['status' => 'occupied']);

        $response = $this->actingAs($this->admin())
            ->postJson('/rooms/bulk-delete', ['room_ids' => [$occupied->id, $free->id]]);

        $response->assertOk()->assertJson(['success' => true, 'deleted' => 1]);
        $this->assertNotNull(Room::find($occupied->id), 'الغرفة المشغولة يجب ألا تُحذف');
        $this->assertNull(Room::find($free->id));
    }

    public function test_admin_can_change_status_of_multiple_rooms_at_once(): void
    {
        $rooms = Room::where('status', 'available')->limit(3)->get();
        $this->assertCount(3, $rooms);

        $response = $this->actingAs($this->admin())
            ->postJson('/rooms/bulk-status', [
                'room_ids' => $rooms->pluck('id')->all(),
                'status'   => 'maintenance',
            ]);

        $response->assertOk()->assertJson(['success' => true, 'updated' => 3]);

        foreach ($rooms as $room) {
            $this->assertSame('maintenance', $room->fresh()->status);
        }
    }

    /** غرفة بها نزيل فعلي تُستثنى بصمت بدل رفض العملية بأكملها */
    public function test_occupied_rooms_are_skipped_not_the_whole_batch(): void
    {
        $occupied = Room::where('status', 'available')->first();
        $free     = Room::where('status', 'available')->where('id', '!=', $occupied->id)->first();

        Reservation::create([
            'guest_id' => Guest::firstOrFail()->id, 'room_id' => $occupied->id,
            'created_by' => $this->admin()->id,
            'check_in_date' => today(), 'check_out_date' => today()->addDays(2),
            'status' => 'checked_in', 'payment_status' => 'unpaid', 'total_amount' => 1000,
        ]);
        $occupied->update(['status' => 'occupied']);

        $response = $this->actingAs($this->admin())
            ->postJson('/rooms/bulk-status', [
                'room_ids' => [$occupied->id, $free->id],
                'status'   => 'under_inspection',
            ]);

        $response->assertOk()->assertJson(['success' => true, 'updated' => 1]);
        $this->assertSame('occupied', $occupied->fresh()->status, 'الغرفة المشغولة يجب ألا تتغيّر');
        $this->assertSame('under_inspection', $free->fresh()->status);
    }

    public function test_rooms_page_shows_the_multi_select_toggle(): void
    {
        $this->actingAs($this->admin())->get('/rooms')
            ->assertOk()
            ->assertSee('تحديد عدة غرف', false)
            ->assertSee('toggleBulkMode', false)
            ->assertSee('applyBulkStatus', false);
    }
}
