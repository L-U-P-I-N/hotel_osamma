<?php
namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Salary;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * كل تصدير PDF في النظام يجب أن يُبنى فعلياً — قوالب التصدير لا تمرّ على
 * المتصفح، فخطأ Blade فيها لا يظهر إلا عند ضغط المستخدم زر التصدير. يرافق ذلك
 * التأكد من أن كلاً منها يمرّر عنوانه للترويسة الموحَّدة.
 */
class PdfExportsSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    /** بيانات كافية كي لا تكون التقارير كلها فارغة. */
    private function seedRecords(): array
    {
        $admin = $this->admin();

        $shift = Shift::create([
            'user_id' => $admin->id, 'shift_date' => today(),
            'started_at' => now()->subHour(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);

        $guest = Guest::create([
            'full_name' => 'نزيل التصدير', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '0155' . random_int(10000, 99999),
        ]);

        $reservation = Reservation::create([
            'guest_id'       => $guest->id,
            'room_id'        => Room::where('status', 'available')->firstOrFail()->id,
            'created_by'     => $admin->id,
            'check_in_date'  => today()->subDay(),
            'check_out_date' => today()->addDay(),
            'check_in_time'  => '14:00',
            'check_out_time' => '13:00',
            'status'         => 'checked_in',
            'payment_status' => 'unpaid',
            'total_amount'   => 40000,
        ]);

        \App\Models\ReservationSegment::create([
            'reservation_id'  => $reservation->id,
            'room_id'         => $reservation->room_id,
            'type'            => 'initial',
            'start_date'      => $reservation->check_in_date,
            'end_date'        => $reservation->check_out_date,
            'nights'          => 2,
            'price_per_night' => 20000,
            'amount'          => 40000,
            'created_by'      => $admin->id,
            'shift_id'        => $shift->id,
        ]);

        $employee = Employee::create([
            'name' => 'موظف التصدير', 'position' => 'استقبال', 'base_salary' => 90000,
            'food_allowance' => 30000, 'hire_date' => today()->subYear(), 'is_active' => true,
        ]);

        $salary = Salary::create([
            'employee_id' => $employee->id, 'month' => now()->month, 'year' => now()->year,
            'base_salary' => 90000, 'bonuses' => 0, 'deductions' => 0,
            'net_salary' => 90000, 'status' => 'draft',
        ]);

        $payment = \App\Models\Payment::create([
            'reservation_id' => $reservation->id, 'shift_id' => $shift->id,
            'received_by' => $admin->id, 'amount' => 15000, 'currency' => 'YER',
            'method' => 'cash', 'payment_date' => now(), 'type' => 'reservation',
        ]);

        return compact('admin', 'shift', 'guest', 'reservation', 'employee', 'salary', 'payment');
    }

    public static function exportRoutes(): array
    {
        // [اسم المسار، معاملات ثابتة، مفتاح النموذج الذي يُمرَّر كمعامل أول]
        return [
            'فاتورة الحجز'            => ['reservations.invoice', [], 'reservation'],
            'فاتورة فترة محدَّدة'      => ['reservations.invoice.partial', ['segment_ids' => 'first'], 'reservation'],
            'كشف حساب نزيل'           => ['guests.statement.pdf', [], 'guest'],
            'كشف حساب موظف'           => ['employees.statement.pdf', [], 'employee'],
            'كشف مسحوبات موظف'        => ['employees.withdrawals.pdf', [], 'employee'],
            'كشف حساب الموظفين'       => ['employees.statements.pdf', [], null],
            'قسيمة راتب'              => ['salaries.pdf', [], 'salary'],
            'تقرير وردية'             => ['shifts.pdf', [], 'shift'],
            'كشف حساب مستخدم'         => ['users.statement.pdf', [], 'admin'],
            'إيصال استلام دفعة'        => ['payments.slip', [], 'payment'],
            'الحضور والغياب'          => ['attendance.pdf', [], null],
            'أرصدة الإجازات'          => ['leaves.report.pdf', [], null],
            'المصروفات'               => ['expenses.pdf', [], null],
            'تقرير عم علي'            => ['reports.amAli.pdf', [], null],
            'إلغاء الحجوزات'          => ['reports.cancelledReservations.pdf', [], null],
            'القائمة اليومية'         => ['reports.daily.pdf', [], null],
            'إغلاق اليوم'             => ['reports.dailyClose.pdf', [], null],
            'الديون'                  => ['reports.debts.pdf', [], null],
            'أعمار الديون'            => ['reports.debts.pdf', ['section' => 'aged'], null],
            'الصندوق العام'           => ['reports.generalSafe.pdf', [], null],
            'الجهات الحكومية'         => ['reports.government.pdf', [], null],
            'النزلاء'                 => ['reports.guests.pdf', [], null],
            'الإشغال'                 => ['reports.occupancy.pdf', [], null],
            'الحجوزات'                => ['reports.reservations.pdf', ['preset' => 'today'], null],
            'الإيرادات'               => ['reports.revenue.pdf', [], null],
            'حالة الغرف'              => ['reports.rooms.pdf', [], null],
            'الرواتب'                 => ['reports.salaries.pdf', [], null],
            'الورديات'                => ['reports.shifts.pdf', [], null],
            'أداء الموظفين'           => ['reports.staff.pdf', [], null],
        ];
    }

    /** @dataProvider exportRoutes */
    public function test_export_renders_a_pdf(string $route, array $params, ?string $modelKey): void
    {
        $records = $this->seedRecords();

        if (($params['segment_ids'] ?? null) === 'first') {
            $params['segment_ids'] = [$records['reservation']->segments()->firstOrFail()->id];
        }

        if ($modelKey !== null) {
            array_unshift($params, $records[$modelKey]);
        }

        $response = $this->actingAs($records['admin'])->get(route($route, $params));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'), $route);
        $this->assertStringStartsWith('%PDF', $response->getContent(), $route);
    }

    /** مستند تسليم الوردية صفحة تُطبع من المتصفح، لا ملف PDF. */
    public function test_shift_handover_page_renders(): void
    {
        $records = $this->seedRecords();

        $this->actingAs($records['admin'])
            ->get(route('shifts.handover', $records['shift']))
            ->assertOk()
            ->assertSee('مستند تسليم الوردية');
    }

    /** نموذج بيانات النزلاء الحكومي يُبنى عبر خدمة لا عبر مسار HTTP. */
    public function test_government_guest_form_renders(): void
    {
        $records = $this->seedRecords();

        $html = view('exports.government_guest', [
            'reservation' => $records['reservation']->load(['guest', 'companions', 'room.roomType', 'createdBy']),
            'hotel'       => \App\Models\Hotel::first(),
        ])->render();

        $this->assertStringContainsString('نموذج بيانات النزلاء', $html);
    }

    /**
     * عنوان كل تصدير يُمرَّر للترويسة الموحَّدة (عمود المستند الأوسط)، ولم يبقَ
     * قالب يرسم ترويسته الخاصة أو يطبع اسم فندق مكتوباً يدوياً.
     */
    public function test_every_pdf_template_uses_the_shared_header_with_a_title(): void
    {
        $templates = [];
        foreach (glob(resource_path('views/**/*.blade.php')) as $file) {
            $body = file_get_contents($file);
            if (str_contains($body, 'pdf_load_view') || !str_contains($body, '<html')) {
                continue;
            }
            if (!str_contains($body, 'NotoNaskh')) {   // قوالب PDF وحدها تُضمّن الخط
                continue;
            }
            $templates[] = [basename(dirname($file)) . '/' . basename($file), $body];
        }

        $this->assertNotEmpty($templates, 'لم يُعثر على قوالب تصدير');

        foreach ($templates as [$name, $body]) {
            $this->assertStringContainsString('partials.pdf-hotel-header-full', $body, $name);
            $this->assertStringContainsString("'docTitle'", $body, $name);
            $this->assertStringNotContainsString('الفندق السعودي</h1>', $body, $name);
        }
    }
}
