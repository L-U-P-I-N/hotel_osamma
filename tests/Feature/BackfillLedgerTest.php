<?php
namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Guest;
use App\Models\JournalEntry;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * ترحيل البيانات التاريخية إلى دفتر الأستاذ.
 *
 * السؤال العملي: بيانات الأشهر الماضية (دفعات، مصروفات، رواتب) مسجَّلة في
 * جداولها بلا قيود. هل تُعاد تعبئتها يدوياً؟ لا — أمرٌ واحد يمرّ عليها ويُنشئ
 * قيدها الغائب، وتشغيله مرتين لا يُنتج قيداً مكرراً، فيصحّ على قاعدة إنتاج حيّة.
 */
class BackfillLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }

    /** حركات تاريخية مُدخَلة مباشرةً بلا قيود — كحال البيانات القائمة. */
    private function seedHistory(): array
    {
        $admin = $this->admin();

        $guest = Guest::create([
            'full_name' => 'نزيل الشهر الماضي', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '04' . random_int(1000000, 9999999),
        ]);
        $reservation = Reservation::create([
            'guest_id' => $guest->id,
            'room_id'  => Room::where('status', 'available')->firstOrFail()->id,
            'created_by' => $admin->id,
            'check_in_date' => today()->subDays(20), 'check_out_date' => today()->subDays(18),
            'status' => 'checked_out', 'payment_status' => 'paid',
            'total_amount' => 40000, 'paid_amount' => 40000,
        ]);

        $payment = Payment::create([
            'reservation_id' => $reservation->id, 'received_by' => $admin->id,
            'amount' => 40000, 'currency' => 'YER', 'method' => 'cash',
            'payment_date' => today()->subDays(20), 'type' => 'reservation',
        ]);

        $expense = Expense::create([
            'amount' => 15000, 'currency' => 'YER', 'category' => 'electricity',
            'description' => 'فاتورة كهرباء الشهر الماضي', 'recipient_name' => 'مؤسسة الكهرباء',
            'expense_date' => today()->subDays(15)->toDateString(),
            'payment_method' => 'cash', 'paid_by' => $admin->id,
        ]);

        // موظف خاص بالاختبار: البذور تُنشئ قسائم رواتب، والقسيمة فريدة
        // بـ(الموظف، الشهر، السنة) فمشاركة موظف قائم تصطدم بها
        $employee = Employee::create([
            'name' => 'موظف الترحيل التاريخي', 'national_id' => '99' . random_int(10000000, 99999999),
            'position' => 'استقبال', 'base_salary' => 100000, 'hire_date' => today()->subYear(),
        ]);
        $salary = Salary::create([
            'employee_id' => $employee->id,
            'month' => (int) today()->subMonth()->format('m'),
            'year'  => (int) today()->subMonth()->format('Y'),
            'base_salary' => 100000, 'net_salary' => 100000,
            'status' => 'paid', 'created_by' => $admin->id,
        ]);

        // لا قيود بعد: هذه هي حالة البيانات التاريخية
        JournalEntry::query()->delete();

        return compact('payment', 'expense', 'salary');
    }

    public function test_the_dry_run_reports_without_writing_anything(): void
    {
        $this->seedHistory();

        $this->artisan('hotel:backfill-ledger --dry-run')->assertSuccessful();

        $this->assertSame(0, JournalEntry::count(), 'وضع المعاينة كتب قيوداً');
    }

    public function test_it_posts_the_missing_entries_for_past_movements(): void
    {
        ['payment' => $payment, 'expense' => $expense, 'salary' => $salary] = $this->seedHistory();

        $this->artisan('hotel:backfill-ledger')->assertSuccessful();

        // دفعة نزيل: نقدية الوردية مقابل إيراد الغرف
        $paymentEntry = JournalEntry::where('source_type', Payment::class)
            ->where('source_id', $payment->id)->where('event', 'payment.received')->firstOrFail();
        $this->assertEqualsWithDelta(40000, $paymentEntry->lines()->where('account_code', '1111')->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(40000, $paymentEntry->lines()->where('account_code', '4110')->sum('credit'), 0.01);

        // مصروف كهرباء: حساب الكهرباء مقابل النقدية
        $expenseEntry = JournalEntry::where('source_type', Expense::class)
            ->where('source_id', $expense->id)->firstOrFail();
        $this->assertEqualsWithDelta(15000, $expenseEntry->lines()->where('account_code', '6410')->sum('debit'), 0.01);

        // الراتب: استحقاق ثم صرف — قيدان لا قيد واحد
        $this->assertSame(2, JournalEntry::where('source_type', Salary::class)->where('source_id', $salary->id)->count());
        $accrual = JournalEntry::where('source_id', $salary->id)->where('event', 'payroll.accrued')->firstOrFail();
        $this->assertEqualsWithDelta(100000, $accrual->lines()->where('account_code', '6113')->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(100000, $accrual->lines()->where('account_code', '2410')->sum('credit'), 0.01);
    }

    /** الاستحقاق والصرف معاً يُرصّدان حساب الرواتب المستحقة — لا انزلاق مدين. */
    public function test_payroll_accrual_and_payment_net_to_zero(): void
    {
        $this->seedHistory();
        $this->artisan('hotel:backfill-ledger')->assertSuccessful();

        $this->assertEqualsWithDelta(
            0,
            \App\Models\ChartOfAccount::where('code', '2410')->first()->balance,
            0.01
        );
    }

    /** إعادة التشغيل لا تُكرّر قيداً — أهم ضمانة لتشغيله على قاعدة حيّة. */
    public function test_running_it_twice_changes_nothing(): void
    {
        $this->seedHistory();

        $this->artisan('hotel:backfill-ledger')->assertSuccessful();
        $afterFirst = [JournalEntry::count(), DB::table('journal_lines')->count(), (float) DB::table('journal_lines')->sum('debit')];

        $this->artisan('hotel:backfill-ledger')->assertSuccessful();
        $afterSecond = [JournalEntry::count(), DB::table('journal_lines')->count(), (float) DB::table('journal_lines')->sum('debit')];

        $this->assertSame($afterFirst, $afterSecond);
    }

    /** وميزان المراجعة يتوازن بعد الترحيل. */
    public function test_the_trial_balance_balances_after_the_backfill(): void
    {
        $this->seedHistory();
        $this->artisan('hotel:backfill-ledger')->assertSuccessful();

        $this->assertEqualsWithDelta(
            (float) DB::table('journal_lines')->sum('debit'),
            (float) DB::table('journal_lines')->sum('credit'),
            0.01
        );
        $this->assertGreaterThan(0, (float) DB::table('journal_lines')->sum('debit'));
    }

    /** وحدّ الفترة يُحترم: ما قبل التاريخ المطلوب لا يُرحَّل. */
    public function test_the_date_range_is_respected(): void
    {
        ['payment' => $payment] = $this->seedHistory();

        $this->artisan('hotel:backfill-ledger --from=' . today()->subDays(5)->toDateString())
            ->assertSuccessful();

        $this->assertSame(
            0,
            JournalEntry::where('source_type', Payment::class)->where('source_id', $payment->id)->count(),
            'رُحِّلت دفعة أقدم من بداية الفترة المطلوبة'
        );
    }
}
