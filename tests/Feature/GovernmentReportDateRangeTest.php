<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تقرير الجهات الحكومية بالفلترة من/إلى يجب أن يعرض كل نزيل كانت إقامته
 * متقاطعة مع المدى المطلوب (دخل قبله وما زال مقيماً أو خرج بعده) لا فقط من
 * دخل بالضبط داخل المدى — فلا يظهر تقريراً ناقصاً عن نزلاء موجودين فعلياً.
 */
class GovernmentReportDateRangeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    private function reservationSpanning(string $checkIn, string $checkOut): Reservation
    {
        $room = Room::where('status', 'available')->firstOrFail();
        $guest = Guest::create([
            'full_name' => 'نزيل ' . uniqid(),
            'nationality' => 'يمني',
        ]);

        return Reservation::create([
            'guest_id'       => $guest->id,
            'room_id'        => $room->id,
            'created_by'     => $this->admin()->id,
            'check_in_date'  => $checkIn,
            'check_out_date' => $checkOut,
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => 10000,
        ]);
    }

    public function test_a_guest_who_checked_in_before_the_range_but_still_staying_is_included(): void
    {
        // دخل قبل المدى المطلوب بأيام، وخرج بعده — إقامته تتقاطع مع المدى بالكامل
        $r = $this->reservationSpanning('2026-08-01', '2026-08-25');

        $response = $this->actingAs($this->admin())
            ->get('/reports/government?from=2026-08-08&to=2026-08-20');

        $response->assertOk();
        $ids = $response->viewData('reservations')->pluck('id');
        $this->assertTrue($ids->contains($r->id), 'يجب أن يظهر النزيل رغم أن دخوله قبل بداية المدى');
    }

    public function test_a_guest_whose_stay_does_not_overlap_the_range_is_excluded(): void
    {
        $r = $this->reservationSpanning('2026-01-01', '2026-01-05');

        $response = $this->actingAs($this->admin())
            ->get('/reports/government?from=2026-08-08&to=2026-08-20');

        $response->assertOk();
        $ids = $response->viewData('reservations')->pluck('id');
        $this->assertFalse($ids->contains($r->id));
    }
}
