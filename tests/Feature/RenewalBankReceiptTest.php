<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * دفعة التجديد بتحويل بنكي دفعةٌ كاملة الأركان كأي دفعة أخرى: تحتاج سند تحويل
 * أو رقم مرجع، ويُحفَظان معها فيظهر السند في تفاصيل الحجز بنفس زر "السند".
 */
class RenewalBankReceiptTest extends TestCase
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

    private function checkedInGuest(): Reservation
    {
        $room = Room::where('status', 'available')->firstOrFail();

        return Reservation::create([
            'guest_id'       => Guest::firstOrFail()->id,
            'room_id'        => $room->id,
            'created_by'     => $this->admin()->id,
            'check_in_date'  => today(),
            'check_out_date' => today()->addDays(2),
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => 40000,
        ]);
    }

    public function test_renewal_stores_the_bank_receipt_with_the_payment(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $this->openShift($admin);
        $r = $this->checkedInGuest();

        $this->actingAs($admin)->post("/reservations/{$r->id}/renew", [
            'new_check_out_date'      => today()->addDays(4)->toDateString(),
            'renewal_price_per_night' => 20000,
            'advance_payment'         => 10000,
            'payment_method'          => 'bank_transfer',
            'bank_transfer_ref'       => 'TRF-2026-777',
            'bank_receipt'            => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertSessionHasNoErrors();

        $payment = $r->payments()->where('type', 'renewal')->firstOrFail();
        $this->assertSame('bank_transfer', $payment->method);
        $this->assertSame('TRF-2026-777', $payment->bank_transfer_ref);
        $this->assertNotNull($payment->bank_receipt_path, 'يجب حفظ مسار سند التحويل مع دفعة التجديد');
    }

    public function test_bank_transfer_renewal_without_any_proof_is_rejected(): void
    {
        $admin = $this->admin();
        $this->openShift($admin);
        $r = $this->checkedInGuest();

        $this->actingAs($admin)->post("/reservations/{$r->id}/renew", [
            'new_check_out_date'      => today()->addDays(4)->toDateString(),
            'renewal_price_per_night' => 20000,
            'advance_payment'         => 10000,
            'payment_method'          => 'bank_transfer',
        ])->assertSessionHasErrors('bank_transfer');

        $this->assertSame(0, $r->payments()->count(), 'لا تُسجَّل دفعة بلا إثبات التحويل');
    }

    /** الدفع نقداً لا يتأثّر: لا يُطلَب سند ولا مرجع */
    public function test_cash_renewal_still_works_without_a_receipt(): void
    {
        $admin = $this->admin();
        $this->openShift($admin);
        $r = $this->checkedInGuest();

        $this->actingAs($admin)->post("/reservations/{$r->id}/renew", [
            'new_check_out_date'      => today()->addDays(4)->toDateString(),
            'renewal_price_per_night' => 20000,
            'advance_payment'         => 10000,
            'payment_method'          => 'cash',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $r->payments()->count());
    }
}
