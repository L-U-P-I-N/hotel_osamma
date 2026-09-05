<?php
namespace Tests\Feature;

use App\Models\Guest;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * زر تسجيل الدخول توقّف عن العمل بلا رسالة لأن نموذج تغيير حالة الغرفة
 * كان متداخلاً داخل نموذج التسجيل — والمتصفح يُسقط النماذج المتداخلة
 * فيتعطّل النموذج الخارجي كلياً.
 */
class CheckInSubmitButtonTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function receptionist(): User { return User::role('receptionist')->firstOrFail(); }

    private function openShift(User $u): Shift
    {
        return Shift::create([
            'user_id' => $u->id, 'shift_date' => today(),
            'started_at' => now()->subHour(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);
    }

    /** استخراج نطاق النموذج الرئيسي والتأكد أنه لا يحوي <form> آخر */
    public function test_check_in_page_has_no_nested_forms(): void
    {
        // غرفة محجوبة حتى تظهر لوحة تغيير الحالة (مصدر العطل)
        Room::where('status', 'available')->firstOrFail()->update(['status' => 'under_inspection']);

        $html = $this->actingAs($this->receptionist())->get('/checkin')
            ->assertOk()
            ->getContent();

        $start = strpos($html, '<form id="checkInMainForm"');
        $this->assertNotFalse($start, 'نموذج التسجيل الرئيسي غير موجود');

        $end = strpos($html, '</form>', $start);
        $this->assertNotFalse($end);

        $mainForm = substr($html, $start + 1, $end - $start - 1);

        $this->assertStringNotContainsString('<form', $mainForm,
            'لا يجوز وجود أي نموذج داخل نموذج تسجيل الدخول — يُعطّل زر الحفظ');
    }

    /** أزرار الحالة ما زالت موجودة ومربوطة بنماذجها خارج النموذج الرئيسي */
    public function test_room_status_buttons_reference_external_forms(): void
    {
        $room = Room::where('status', 'available')->firstOrFail();
        $room->update(['status' => 'under_inspection']);

        $html = $this->actingAs($this->receptionist())->get('/checkin')->assertOk()->getContent();

        $this->assertStringContainsString('form="roomStatus-' . $room->id . '-available"', $html);
        $this->assertStringContainsString('id="roomStatus-' . $room->id . '-available"', $html);
    }

    /** والأهم: التسجيل يعمل فعلاً من طرف الخادم */
    public function test_check_in_actually_registers_a_guest(): void
    {
        $employee = $this->receptionist();
        $this->openShift($employee);

        Storage::fake('private');
        $room = Room::with('roomType')->where('status', 'available')->firstOrFail();

        $response = $this->actingAs($employee)->post('/checkin', [
            'full_name'       => 'نزيل اختبار الزر',
            'id_type'         => 'national_id',
            'id_number'       => 'BTN-' . uniqid(),
            'nationality'     => 'يمني',
            'phone'           => '0711223344',
            'room_id'         => $room->id,
            'check_in_date'   => today()->toDateString(),
            'check_out_date'  => today()->addDays(2)->toDateString(),
            'payment_status'  => 'unpaid',
            'price_per_night' => (float) $room->priceFor('YER'),
            'id_image'        => UploadedFile::fake()->image('id.jpg'),
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('guests', ['full_name' => 'نزيل اختبار الزر']);
        $this->assertSame('occupied', $room->fresh()->status);
    }

    /** المسار الفعلي الذي يسلكه الزر: إرسال AJAX يتوقّع JSON فيه redirect */
    public function test_ajax_submit_returns_json_redirect(): void
    {
        $employee = $this->receptionist();
        $this->openShift($employee);
        Storage::fake('private');

        $room = Room::with('roomType')->where('status', 'available')->firstOrFail();

        $response = $this->actingAs($employee)
            ->postJson('/checkin', [
                'full_name'       => 'نزيل عبر AJAX',
                'id_type'         => 'national_id',
                'id_number'       => 'AJX-' . uniqid(),
                'nationality'     => 'يمني',
                'phone'           => '0711223344',
                'room_id'         => $room->id,
                'check_in_date'   => today()->toDateString(),
                'check_out_date'  => today()->addDays(2)->toDateString(),
                'payment_status'  => 'unpaid',
                'price_per_night' => (float) $room->priceFor('YER'),
                'id_image'        => UploadedFile::fake()->image('id.jpg'),
            ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'redirect']);

        $this->assertDatabaseHas('guests', ['full_name' => 'نزيل عبر AJAX']);
    }

    /** الأخطاء تصل للموظف بصيغة JSON بدل فشل صامت */
    public function test_validation_errors_reach_the_employee(): void
    {
        $employee = $this->receptionist();
        $this->openShift($employee);

        $this->actingAs($employee)
            ->postJson('/checkin', ['full_name' => 'ناقص البيانات'])
            ->assertStatus(422)
            ->assertJsonStructure(['errors']);
    }
}
