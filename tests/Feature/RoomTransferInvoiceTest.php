<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use App\Services\ReservationSegmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * نقل النزيل بين الغرف يجب أن يُقسِّم فترة المحاسبة عند تاريخ النقل — الغرفة
 * القديمة تحتفظ بفترتها وسعرها، والجديدة تُسجَّل كفترة مستقلة. هذا ما يتيح لاحقاً
 * طباعة فاتورة جزئية تخصّ الغرفة السابقة وحدها، أو أياماً محدَّدة من الإقامة.
 */
class RoomTransferInvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function openShift(User $user): Shift
    {
        return Shift::create([
            'user_id' => $user->id, 'shift_date' => today(),
            'started_at' => now()->subHour(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);
    }

    private function checkedInGuest(float $nightly = 20000, int $nights = 4): Reservation
    {
        $room = Room::with('roomType')->where('status', 'available')->firstOrFail();
        $room->roomType->update(['min_price' => 10000, 'base_price' => $nightly, 'max_price' => 50000]);
        $room->update(['price_yer' => $nightly]);

        return Reservation::create([
            'guest_id'       => Guest::firstOrFail()->id,
            'room_id'        => $room->id,
            'created_by'     => $this->admin()->id,
            'check_in_date'  => today()->subDays($nights),
            'check_out_date' => today(),
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => $nightly * $nights,
            'paid_amount'    => 0,
        ]);
    }

    /**
     * اختبار مباشر لمنطق تقسيم الفترة في الخدمة نفسها (recordTransfer) — بمعزل عن
     * حساب الليالي/الأسعار في transferRoom() الذي لم يتغيّر بهذه الميزة.
     */
    public function test_record_transfer_splits_segment_by_room_and_date(): void
    {
        $admin = $this->admin();
        $r = $this->checkedInGuest(nightly: 20000, nights: 4);

        $service = app(ReservationSegmentService::class);
        $service->recordInitial($r, 20000, 20000, 4, $admin->id);

        $newRoomId = Room::where('id', '!=', $r->room_id)->value('id');

        $transferDate = $r->check_in_date->copy()->addDays(2); // منتصف الإقامة
        $service->recordTransfer($r, $transferDate, $newRoomId, 30000, $admin->id);

        $segments = $r->segments()->orderBy('start_date')->get();
        $this->assertSame(2, $segments->count(), 'يجب أن تُقسَم الفترة إلى فترتين عند النقل');

        $before = $segments[0];
        $after  = $segments[1];

        $this->assertSame($r->room_id, $before->room_id, 'الفترة الأولى تبقى بالغرفة القديمة');
        $this->assertSame($newRoomId, $after->room_id, 'الفترة الثانية بالغرفة الجديدة');
        $this->assertEqualsWithDelta(20000, (float) $before->price_per_night, 0.01);
        $this->assertEqualsWithDelta(30000, (float) $after->price_per_night, 0.01);
        $this->assertSame(2, $before->nights);
        $this->assertSame(2, $after->nights);
        $this->assertTrue($after->isRoomChange(), 'الفترة بعد النقل تُعتبر تغيير غرفة');
        $this->assertSame('تغيير غرفة', $after->type_label);
        $this->assertFalse($before->isRoomChange());
    }

    /** النقل عبر شاشة الحجز الفعلية (HTTP) يجب أن يُنشئ فترة مستقلة للغرفة الجديدة أيضاً */
    public function test_transfer_room_endpoint_creates_a_room_change_segment(): void
    {
        $admin = $this->admin();
        $this->openShift($admin);
        $r = $this->checkedInGuest(nightly: 20000, nights: 4);
        app(ReservationSegmentService::class)->recordInitial($r, 20000, 20000, 4, $admin->id);

        $oldRoom = $r->room;
        $newRoom = Room::with('roomType')->where('status', 'available')->where('id', '!=', $oldRoom->id)->firstOrFail();
        $newRoom->roomType->update(['min_price' => 10000, 'base_price' => 30000, 'max_price' => 50000]);
        $newRoom->update(['price_yer' => 30000]);

        $this->actingAs($admin)
            ->post("/reservations/{$r->id}/transfer-room", ['new_room_selection' => (string) $newRoom->id])
            ->assertSessionHasNoErrors();

        $r->refresh();
        $segments = $r->segments()->orderBy('start_date')->get();
        $this->assertGreaterThanOrEqual(2, $segments->count(), 'يجب إضافة فترة مستقلة للغرفة الجديدة');
        $this->assertSame($newRoom->id, $segments->last()->room_id);
        $this->assertSame($oldRoom->id, $segments->first()->room_id);
    }

    public function test_partial_invoice_can_be_printed_for_the_old_room_only_after_transfer(): void
    {
        $admin = $this->admin();
        $r = $this->checkedInGuest(nightly: 20000, nights: 4);
        $service = app(ReservationSegmentService::class);
        $service->recordInitial($r, 20000, 20000, 4, $admin->id);
        $newRoomId = Room::where('id', '!=', $r->room_id)->value('id');
        $service->recordTransfer($r, $r->check_in_date->copy()->addDays(2), $newRoomId, 30000, $admin->id);

        $oldSegment = $r->segments()->where('room_id', $r->room_id)->firstOrFail();

        $response = $this->actingAs($admin)
            ->get("/reservations/{$r->id}/invoice/partial?" . http_build_query(['segment_ids' => [$oldSegment->id]]));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_partial_invoice_requires_at_least_one_segment(): void
    {
        $admin = $this->admin();
        $r = $this->checkedInGuest();
        app(ReservationSegmentService::class)->recordInitial($r, 20000, 20000, 4, $admin->id);

        $this->actingAs($admin)
            ->get("/reservations/{$r->id}/invoice/partial")
            ->assertSessionHasErrors('segment_ids');
    }

    /**
     * تصحيح يدوي لغرفة فترة قديمة لا يوجد لها سجل مراجعة دقيق (نقل تم قبل هذه
     * الميزة، أو بطريقة أخرى لم تُسجَّل) — الموظف يختار الغرفة الصحيحة يدوياً.
     */
    public function test_staff_can_manually_correct_a_segments_room(): void
    {
        $admin = $this->admin();
        $this->openShift($admin);
        $r = $this->checkedInGuest(nightly: 20000, nights: 4);
        app(ReservationSegmentService::class)->recordInitial($r, 20000, 20000, 4, $admin->id);

        $segment = $r->segments()->firstOrFail();
        $this->assertSame($r->room_id, $segment->room_id);

        $correctRoomId = Room::where('id', '!=', $r->room_id)->value('id');

        $this->actingAs($admin)
            ->put("/reservations/segment/{$segment->id}", [
                'price_per_night' => (float) $segment->price_per_night,
                'room_id'         => $correctRoomId,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($correctRoomId, $segment->fresh()->room_id);
    }
}
