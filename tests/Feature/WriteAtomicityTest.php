<?php

namespace Tests\Feature;

use App\Models\CashWithdrawal;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Room;
use App\Models\Salary;
use App\Models\Shift;
use App\Models\User;
use App\Services\ShiftService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * العمليات التي تكتب أكثر من سجل يجب أن تتم كلها أو لا يبقى منها شيء.
 *
 * كشفها فحصٌ تلاه عطلُ إنشاء الوعاء المالي: كل هذه المسارات كانت تكتب سجلّين
 * أو ثلاثة بلا معاملة، فيترك الفشل في منتصفها نصف عملية — ولا تُظهر الشاشة
 * إلا رسالة خطأ توحي بأن شيئاً لم يحدث.
 *
 * الفشل يُفتعَل بمستمع يرمي عند الكتابة الثانية، فيقيس الاختبار العقد نفسه
 * (الذرّية) لا خصوصية سائق قاعدة بعينه — وهو ما يجعل هذه الأعطال تمرّ في
 * التطوير على sqlite وتظهر في الإنتاج على MySQL وحده.
 */
class WriteAtomicityTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
    }

    private function admin(): User
    {
        return User::role('admin')->firstOrFail();
    }

    /** ينفّذ العملية ويبتلع الخطأ المُفتعَل — المقصود ما يبقى في القاعدة بعده */
    private function expectFailure(callable $operation): void
    {
        try {
            $operation();
        } catch (\Throwable) {
            // متوقَّع
        }
    }

    /* ═══ الجناح: قسمان يُنشآن معاً ═══ */

    public function test_a_failed_suite_leaves_no_half_room(): void
    {
        $before = Room::withTrashed()->count();

        // القسم الثاني يفشل — وهو ما كان يترك suite_a وحيدة بلا رابط
        Room::creating(static function (Room $room) {
            if ($room->room_sub_type === 'suite_b') {
                throw new \RuntimeException('فشل مُفتعَل عند إنشاء القسم الثاني');
            }
        });

        $this->expectFailure(fn () => $this->actingAs($this->admin())->post(route('rooms.store'), [
            'room_number'   => '905',
            'floor'         => 9,
            'room_sub_type' => 'suite',
            'beds_count'    => 2,
        ]));

        $this->assertSame($before, Room::withTrashed()->count(), 'بقي نصف جناح في قائمة الغرف');
    }

    /* ═══ الراتب: حالة القسيمة وقيداها ═══ */

    public function test_a_failed_salary_payment_leaves_neither_a_paid_slip_nor_a_half_ledger(): void
    {
        $employee = Employee::create([
            'name' => 'موظف الذرّية', 'national_id' => '77' . random_int(10000000, 99999999),
            'position' => 'استقبال', 'base_salary' => 90000, 'hire_date' => today()->subYear(),
        ]);
        $salary = Salary::create([
            'employee_id' => $employee->id,
            'month' => (int) today()->format('m'), 'year' => (int) today()->format('Y'),
            'base_salary' => 90000, 'net_salary' => 90000,
            'status' => 'pending', 'created_by' => $this->admin()->id,
        ]);

        $entriesBefore = JournalEntry::count();

        // قيد الصرف يفشل بعد قيد الاستحقاق — فيبقى الراتب «مدفوعاً» ودينه مفتوحاً
        JournalEntry::created(static function (JournalEntry $entry) {
            if ($entry->event === 'payroll.paid') {
                throw new \RuntimeException('فشل مُفتعَل عند قيد الصرف');
            }
        });

        $this->expectFailure(fn () => $this->actingAs($this->admin())
            ->patch(route('salaries.markPaid', $salary), ['payment_method' => 'cash']));

        $this->assertSame('pending', $salary->fresh()->status, 'القسيمة بقيت مدفوعة بلا قيد صرف');
        $this->assertSame($entriesBefore, JournalEntry::count(), 'بقي قيد استحقاق بلا صرف يقابله');
    }

    /* ═══ السحب: مصروف وسحب وقيد ═══ */

    public function test_a_failed_withdrawal_leaves_no_expense_behind(): void
    {
        $user  = $this->admin();
        $shift = Shift::create([
            'user_id' => $user->id, 'shift_date' => today(),
            'started_at' => now()->subHour(), 'is_closed' => false, 'opening_balance_yer' => 0,
        ]);

        $expensesBefore = Expense::count();

        // السحب يفشل بعد إنشاء المصروف — رقمٌ في المصروفات بلا أثر في الصندوق
        CashWithdrawal::creating(static function () {
            throw new \RuntimeException('فشل مُفتعَل عند إنشاء السحب');
        });

        $this->actingAs($user);
        $this->expectFailure(fn () => app(ShiftService::class)->addWithdrawal($shift, [
            'amount'            => 5000,
            'currency'          => 'YER',
            'withdrawn_by_name' => 'مورّد',
            'handed_by_name'    => $user->name,
            'category'          => 'other',
        ]));

        $this->assertSame($expensesBefore, Expense::count(), 'بقي مصروف بلا سحبٍ يقابله');
    }
}
