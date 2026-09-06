<?php
namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use App\Services\ReservationSegmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * حجز نُقل بين غرف قبل ميزة تسجيل الغرفة على الفترات: كل فتراته صارت (خطأً) على
 * الغرفة الحالية فقط. هذا الأمر يستخدم سجل المراجعة لإعادة توزيعها على الغرف
 * الصحيحة تاريخياً، محاكياً بالضبط الحالة الواقعية التي وصفها صاحب الفندق.
 */
class BackfillSegmentRoomsFromHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    public function test_it_re_splits_segments_using_audit_log_transfer_history(): void
    {
        $admin = $this->admin();
        $roomA = Room::with('roomType')->where('status', 'available')->firstOrFail();
        $roomB = Room::with('roomType')->where('status', 'available')->where('id', '!=', $roomA->id)->firstOrFail();

        $checkIn = today()->subDays(6);
        $r = Reservation::create([
            'guest_id'       => Guest::firstOrFail()->id,
            'room_id'        => $roomB->id, // الغرفة الحالية بعد النقل (خطأً ستُنسَب لها كل الفترات)
            'created_by'     => $admin->id,
            'check_in_date'  => $checkIn,
            'check_out_date' => today(),
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => 120000,
            'paid_amount'    => 0,
        ]);

        // فترة واحدة تغطي كامل الإقامة، لكنها بغرفة B الحالية بدل A التي بدأ فيها فعلاً
        app(ReservationSegmentService::class)->recordInitial($r, 20000, 20000, 6, $admin->id);
        $segment = $r->segments()->firstOrFail();
        $segment->update(['room_id' => $roomB->id]);

        // سجل نقل تاريخي: من A إلى B، حدث بعد 3 ليالٍ من الوصول
        $transferDate = $checkIn->copy()->addDays(3);
        $log = AuditLog::create([
            'user_id'     => $admin->id,
            'action'      => 'update',
            'model_type'  => Reservation::class,
            'model_id'    => $r->id,
            'old_values'  => ['room_id' => $roomA->id, 'total_amount' => 120000],
            'new_values'  => ['room_id' => $roomB->id, 'total_amount' => 120000, 'action' => 'room_transfer'],
        ]);
        // created_at ليس fillable — نضبطه مباشرة لمحاكاة تاريخ نقل حقيقي في الماضي
        $log->timestamps = false;
        $log->created_at = $transferDate;
        $log->updated_at = $transferDate;
        $log->save();

        $this->artisan('segments:backfill-rooms')->assertSuccessful();

        $segments = $r->segments()->orderBy('start_date')->get();
        $this->assertSame(2, $segments->count(), 'يجب أن تُقسَم الفترة الواحدة إلى فترتين عند نقطة النقل');
        $this->assertSame($roomA->id, $segments[0]->room_id, 'الفترة الأولى يجب أن تعود للغرفة A الصحيحة تاريخياً');
        $this->assertSame($roomB->id, $segments[1]->room_id, 'الفترة الثانية للغرفة B بعد النقل');
        $this->assertSame(3, $segments[0]->nights);
        $this->assertSame(3, $segments[1]->nights);

        // إعادة التشغيل لا تُغيّر شيئاً (نقاط الانكسار على حدود الفترات بالفعل)
        $this->artisan('segments:backfill-rooms')->assertSuccessful();
        $this->assertSame(2, $r->segments()->count());
    }
}
