<?php
namespace Tests\Feature;

use App\Models\ChartOfAccount;
use Database\Seeders\AccountsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ترحيلة توحيد دفتر الأستاذ: قابلة للاستئناف ومستقلة عن ترتيب البذور.
 *
 * MySQL يُثبّت تغييرات البنية فور تنفيذها ولا يتراجع عنها مع المعاملة، فتوقّف
 * الترحيلة في منتصفها كان يترك العمود مضافاً بينما لا تُسجَّل الترحيلة كمنفَّذة —
 * وكل إعادة نشر تصطدم بـ«العمود موجود سلفاً» ولا تُكمل أبداً. وقد وقع ذلك فعلاً
 * على الإنتاج، فهذه الاختبارات تحرس الحالتين اللتين أوقفتاه.
 */
class LedgerUnificationMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function migration(): object
    {
        return require database_path('migrations/2026_09_29_100000_unify_ledger_on_chart_of_accounts.php');
    }

    private function codeMap(): array
    {
        return (new \ReflectionClass($this->migration()))->getConstant('CODE_MAP');
    }

    /** إعادة التشغيل بعد الاكتمال لا ترمي ولا تُغيّر البنية. */
    public function test_running_it_again_after_completion_is_a_no_op(): void
    {
        $this->assertFalse(Schema::hasColumn('journal_lines', 'account_id'));
        $this->assertTrue(Schema::hasColumn('journal_lines', 'account_code'));

        $this->migration()->up();

        $this->assertFalse(Schema::hasColumn('journal_lines', 'account_id'));
        $this->assertTrue(Schema::hasColumn('journal_lines', 'account_code'));
    }

    /**
     * الاستئناف من منتصف الطريق: العمود مضاف والترحيلة غير مسجَّلة.
     * هذه هي الحالة التي عطّلت الإنتاج — MySQL لا يتراجع عن تغييرات البنية، فبقي
     * العمود بينما لم تُسجَّل الترحيلة، فكانت كل إعادة نشر تصطدم به وتتوقّف.
     *
     * والسطران المُختاران هما بعينهما ما أوقف المحاولة الأولى: 5300 رواتب و1300
     * ديون المشتريات، وكوداهما الجديدان (6113 و1265) أُضيفا للشجرة في نفس الإصدار.
     */
    public function test_it_resumes_when_the_column_was_already_added(): void
    {
        $migration = $this->migration();

        // العودة لما قبل التوحيد: down() تُعيد account_id وتُسقط account_code
        $migration->down();
        (new AccountsSeeder())->run();

        // الكودان الجديدان يُحذفان كي تحاكي الحالة الإنتاج: البذور تعمل بعد
        // الترحيلات، فلحظة النقل لم يكن 6113 ولا 1265 موجوداً — وهذا ما رمى
        // «19 سطراً بلا حساب». الترحيلة نفسها هي من يجب أن تُعيدهما.
        DB::table('chart_of_accounts')->whereIn('code', ['6113', '1265'])->delete();
        $this->assertSame(0, ChartOfAccount::whereIn('code', ['6113', '1265'])->count());

        $legacy = DB::table('accounts')->pluck('id', 'code');
        $entryId = DB::table('journal_entries')->insertGetId([
            'entry_date' => '2026-09-01',
            'description' => 'قيد تجريبي قبل التوحيد',
            'source_type' => 'test',
            'source_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_lines')->insert([
            [
                'journal_entry_id' => $entryId,
                'account_id' => $legacy['5300'],
                'debit' => 500.00,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'journal_entry_id' => $entryId,
                'account_id' => $legacy['1300'],
                'debit' => 0,
                'credit' => 500.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // العمود مضاف والترحيلة غير مسجَّلة — حالة الإنتاج العالقة بالحرف
        Schema::table('journal_lines', function (Blueprint $table) {
            $table->string('account_code', 20)->nullable()->after('account_id');
        });

        $migration->up();

        $this->assertFalse(Schema::hasColumn('journal_lines', 'account_id'), 'لم تُكمل الترحيلة');

        $codes = DB::table('journal_lines')->orderBy('id')->pluck('account_code')->all();
        $this->assertSame(['6113', '1265'], $codes, 'لم تُنقل السطور إلى أكوادها الجديدة');
    }

    /**
     * أكواد الوجهة كلها موجودة في الشجرة — بما فيها ما أُضيف في نفس الإصدار.
     * غياب أحدها هو ما أوقف النشر: البذور تعمل بعد الترحيلات، فكانت 6113 و1265
     * غير موجودتين لحظة النقل فتعذّر نقل سطور الرواتب وديون المشتريات.
     */
    public function test_every_mapping_target_exists_and_is_active(): void
    {
        $map = $this->codeMap();
        $this->assertNotEmpty($map, 'خريطة النقل غير مقروءة');

        foreach ($map as $legacy => $target) {
            $account = ChartOfAccount::where('code', $target)->first();

            $this->assertNotNull($account, "كود الوجهة {$target} (من {$legacy}) غير موجود في الشجرة");
            $this->assertTrue((bool) $account->is_active, "كود الوجهة {$target} موقوف");
        }
    }

    /** وكل حساب في الشجرة القديمة له مقابل — وإلا تعطّل النشر على سطوره. */
    public function test_every_legacy_account_has_a_mapping(): void
    {
        $map = $this->codeMap();

        // أكواد الشجرة القديمة تُقرأ من بذرتها — مصدرها الوحيد
        preg_match_all(
            "/\\['(\\d{4})',\\s*'/u",
            file_get_contents(database_path('seeders/AccountsSeeder.php')),
            $matches
        );

        $this->assertNotEmpty($matches[1], 'لم تُقرأ أكواد الشجرة القديمة');

        foreach (array_unique($matches[1]) as $code) {
            $this->assertArrayHasKey(
                $code,
                $map,
                "الحساب القديم {$code} بلا تحويل في خريطة النقل — سطوره ستعطّل النشر"
            );
        }
    }

    /** والحساب الذي أُضيف لاحقاً (2300 متبقيات النزلاء) مشمول كذلك. */
    public function test_the_guest_credit_account_is_mapped(): void
    {
        $map = $this->codeMap();

        $this->assertArrayHasKey('2300', $map);
        $this->assertSame('2230', $map['2300'], 'متبقيات النزلاء يجب أن تذهب لأرصدة النزلاء الدائنة لا للضرائب');
    }
}
