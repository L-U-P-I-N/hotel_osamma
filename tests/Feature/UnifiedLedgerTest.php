<?php
namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\Guest;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Shift;
use App\Models\User;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * المرحلة صفر: دفتر أستاذ واحد.
 *
 * كان النظام يحمل شجرتين متوازيتين — القيود تُرحَّل إلى جدول accounts الصغير،
 * وشجرة USALI تُعرَض ولا يُرحَّل إليها — والأكواد بينهما متصادمة المعاني. هذه
 * الاختبارات تحرس التوحيد: كل قيد يذهب إلى ورقة نشطة في شجرة USALI، ولا يُقبل
 * ترحيل على حساب أب، ولا يُنتج الحدث الواحد قيدين.
 */
class UnifiedLedgerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function journal(): JournalService { return app(JournalService::class); }

    /* ═══ الشجرة الموحّدة ═══ */

    public function test_the_ledger_posts_into_the_usali_tree(): void
    {
        $entry = $this->journal()->postOrFail(
            today()->toDateString(), 'اختبار الترحيل', 'test', 1,
            [
                ['account_code' => '1111', 'debit'  => 1000],
                ['account_code' => '4110', 'credit' => 1000],
            ],
            null, 'test.post'
        );

        $line = $entry->lines()->first();

        $this->assertSame('1111', $line->account_code);
        $this->assertInstanceOf(ChartOfAccount::class, $line->account);
        $this->assertSame('درج نقدية الوردية', $line->account->name_ar);
    }

    /** الشجرة القديمة لم تعد تُبذر ولا يُرحَّل إليها. */
    public function test_the_legacy_tree_is_no_longer_seeded(): void
    {
        $this->assertSame(0, DB::table('accounts')->count());
        $this->assertGreaterThan(150, ChartOfAccount::count());
        // العمود القديم أُزيل من سطور القيد
        $this->assertFalse(\Schema::hasColumn('journal_lines', 'account_id'));
        $this->assertTrue(\Schema::hasColumn('journal_lines', 'account_code'));
    }

    /**
     * الترحيل على حساب أب يُحتسب مرتين في أي تجميع (في الأب وفي مجموع أبنائه)،
     * فيُرفض عند الباب لا يُكتشف في تقرير لاحق.
     */
    public function test_posting_to_a_rollup_account_is_refused(): void
    {
        $this->expectExceptionMessage('حسابٌ أب تجميعي');

        $this->journal()->postOrFail(
            today()->toDateString(), 'ترحيل على حساب أب', 'test', 2,
            [
                ['account_code' => '1100', 'debit'  => 500],   // Cash & Bank — أب
                ['account_code' => '4110', 'credit' => 500],
            ],
            null, 'test.rollup'
        );
    }

    public function test_posting_to_an_inactive_or_unknown_account_is_refused(): void
    {
        ChartOfAccount::where('code', '4110')->update(['is_active' => false]);

        try {
            $this->journal()->postOrFail(
                today()->toDateString(), 'حساب موقوف', 'test', 3,
                [['account_code' => '1111', 'debit' => 100], ['account_code' => '4110', 'credit' => 100]],
                null, 'test.inactive'
            );
            $this->fail('كان يجب رفض الترحيل على حساب موقوف');
        } catch (\Throwable $e) {
            $this->assertStringContainsString('موقوف', $e->getMessage());
        }

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->journal()->postOrFail(
            today()->toDateString(), 'حساب مجهول', 'test', 4,
            [['account_code' => '9999', 'debit' => 100], ['account_code' => '1111', 'credit' => 100]],
            null, 'test.unknown'
        );
    }

    /* ═══ سلامة القيد ═══ */

    public function test_an_unbalanced_or_malformed_entry_is_refused(): void
    {
        $cases = [
            'غير متوازن' => [['account_code' => '1111', 'debit' => 100], ['account_code' => '4110', 'credit' => 90]],
            'سطر واحد'   => [['account_code' => '1111', 'debit' => 100]],
            'مبلغ سالب'  => [['account_code' => '1111', 'debit' => -100], ['account_code' => '4110', 'credit' => -100]],
            'الطرفان معاً' => [['account_code' => '1111', 'debit' => 100, 'credit' => 100], ['account_code' => '4110', 'credit' => 100]],
        ];

        foreach ($cases as $label => $lines) {
            try {
                $this->journal()->postOrFail(today()->toDateString(), $label, 'test', 9, $lines, null, 'test.' . $label);
                $this->fail("كان يجب رفض القيد: {$label}");
            } catch (\InvalidArgumentException $e) {
                $this->assertNotEmpty($e->getMessage(), $label);
            }
        }

        $this->assertSame(0, JournalEntry::where('source_type', 'test')->count());
    }

    /**
     * الحدث الواحد لا يُنتج قيدين مهما تكرّر استدعاؤه — شرطٌ لازم لترحيل
     * البيانات التاريخية ولإعادة تشغيل عملية فشلت.
     */
    public function test_the_same_event_never_posts_twice(): void
    {
        $lines = [
            ['account_code' => '1111', 'debit'  => 700],
            ['account_code' => '4110', 'credit' => 700],
        ];

        $first  = $this->journal()->postOrFail(today()->toDateString(), 'أول', 'test', 5, $lines, null, 'test.once');
        $second = $this->journal()->postOrFail(today()->toDateString(), 'ثانٍ', 'test', 5, $lines, null, 'test.once');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, JournalEntry::where('source_type', 'test')->where('source_id', 5)->count());
        $this->assertSame(2, JournalLine::whereIn('account_code', ['1111', '4110'])->count());
    }

    /** وحدثان مختلفان لنفس المصدر يُرحَّلان قيدين مستقلين (استحقاق ثم صرف). */
    public function test_two_events_on_one_source_are_two_entries(): void
    {
        $this->journal()->postOrFail(today()->toDateString(), 'استحقاق', 'test', 6,
            [['account_code' => '6113', 'debit' => 300], ['account_code' => '2410', 'credit' => 300]], null, 'payroll.accrued');
        $this->journal()->postOrFail(today()->toDateString(), 'صرف', 'test', 6,
            [['account_code' => '2410', 'debit' => 300], ['account_code' => '1120', 'credit' => 300]], null, 'payroll.paid');

        $this->assertSame(2, JournalEntry::where('source_type', 'test')->where('source_id', 6)->count());
        $this->assertEquals(0, ChartOfAccount::where('code', '2410')->first()->balance);
    }

    /* ═══ كل كود في الشيفرة يجب أن يكون ورقة صالحة ═══ */

    /**
     * أكثر الأخطاء احتمالاً بعد التوحيد: كود يشير إلى حساب أب أو إلى حساب غير
     * موجود، فلا يظهر إلا لحظة وقوع العملية على نزيل حقيقي. هذا الاختبار يمسح
     * كل أكواد الترحيل في الشيفرة ويتحقق منها مقدماً.
     */
    public function test_every_posting_code_in_the_codebase_is_a_valid_leaf(): void
    {
        $sources = collect(\File::allFiles(app_path()))
            ->filter(fn ($f) => $f->getExtension() === 'php');

        $codes = collect();
        foreach ($sources as $file) {
            preg_match_all("/'account_code'\s*=>\s*'(\d{4})'/", $file->getContents(), $m);
            $codes = $codes->merge($m[1]);
        }
        // أكواد خريطة فئات المصروف تُبنى ديناميكياً فتُقرأ من مصدرها
        foreach (['maintenance', 'electricity', 'salary', 'cleaning', 'food', 'other'] as $category) {
            $codes->push(\App\Models\Expense::categoryAccountCode($category));
        }
        // أكواد خدمة متبقيات النزلاء ثوابت في الصنف
        $reflection = new \ReflectionClass(\App\Services\GuestCreditService::class);
        foreach ($reflection->getConstants() as $value) {
            if (is_string($value) && preg_match('/^\d{4}$/', $value)) {
                $codes->push($value);
            }
        }

        $codes = $codes->unique()->values();
        $this->assertGreaterThan(8, $codes->count(), 'لم تُلتقط أكواد الترحيل من الشيفرة');

        foreach ($codes as $code) {
            $account = ChartOfAccount::where('code', $code)->first();

            $this->assertNotNull($account, "الكود {$code} مستعمَل في الشيفرة وغير موجود في الشجرة");
            $this->assertTrue((bool) $account->is_active, "الكود {$code} ({$account->name_ar}) موقوف");
            $this->assertTrue(
                (bool) $account->is_posting,
                "الكود {$code} ({$account->name_ar}) حساب أب تجميعي — الترحيل عليه يُحتسب مرتين"
            );
        }
    }

    /* ═══ مسار تشغيلي كامل ═══ */

    public function test_a_guest_payment_lands_in_the_unified_tree(): void
    {
        $admin = User::role('admin')->firstOrFail();
        Shift::firstOrCreate(
            ['user_id' => $admin->id, 'shift_date' => today(), 'is_closed' => false],
            ['started_at' => now()->subHour(), 'opening_balance_yer' => 0]
        );

        $guest = Guest::create([
            'full_name' => 'نزيل القيد', 'nationality' => 'يمني',
            'id_type' => 'national_id', 'id_number' => '04' . random_int(1000000, 9999999),
        ]);
        $reservation = Reservation::create([
            'guest_id' => $guest->id,
            'room_id'  => Room::where('status', 'available')->firstOrFail()->id,
            'created_by' => $admin->id,
            'check_in_date' => today(), 'check_out_date' => today()->addDay(),
            'status' => 'checked_in', 'payment_status' => 'unpaid',
            'total_amount' => 30000, 'paid_amount' => 0,
        ]);

        app(\App\Services\PaymentService::class)->addPayment($reservation, [
            'amount' => 30000, 'currency' => 'YER', 'method' => 'cash', 'type' => 'reservation',
        ], $admin);

        $payment = Payment::where('reservation_id', $reservation->id)->firstOrFail();
        $entry   = JournalEntry::where('source_type', Payment::class)->where('source_id', $payment->id)->firstOrFail();

        $this->assertEqualsWithDelta(30000, $entry->lines()->where('account_code', '1111')->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(30000, $entry->lines()->where('account_code', '4110')->sum('credit'), 0.01);
        // ورصيد الحساب يُقرأ من الشجرة الموحّدة
        $this->assertEqualsWithDelta(30000, ChartOfAccount::where('code', '4110')->first()->balance, 0.01);
    }

    /** وشاشة شجرة الحسابات تُبنى من الشجرة الموحّدة وتعرض أرصدتها. */
    public function test_the_accounts_screen_renders_the_unified_tree(): void
    {
        $this->journal()->postOrFail(today()->toDateString(), 'دفعة', 'test', 30,
            [['account_code' => '1111', 'debit' => 9000], ['account_code' => '4110', 'credit' => 9000]], null, 'screen');

        $this->actingAs(User::role('admin')->firstOrFail())
            ->get(route('reports.financeHub', ['tab' => 'accounts']))
            ->assertOk()
            ->assertSee('إيراد السعر المعلن', false)
            ->assertSee('درج نقدية الوردية', false)
            ->assertSee('9,000');
    }

    /** وميزان المراجعة يتوازن دائماً — مجموع المدين = مجموع الدائن. */
    public function test_the_trial_balance_always_balances(): void
    {
        $this->journal()->postOrFail(today()->toDateString(), 'أ', 'test', 20,
            [['account_code' => '1111', 'debit' => 5000], ['account_code' => '4110', 'credit' => 5000]], null, 'e1');
        $this->journal()->postOrFail(today()->toDateString(), 'ب', 'test', 21,
            [['account_code' => '6410', 'debit' => 1200], ['account_code' => '1111', 'credit' => 1200]], null, 'e2');

        $this->assertEqualsWithDelta(
            (float) DB::table('journal_lines')->sum('debit'),
            (float) DB::table('journal_lines')->sum('credit'),
            0.01
        );
    }
}
