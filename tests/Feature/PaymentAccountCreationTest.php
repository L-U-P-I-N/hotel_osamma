<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\PaymentAccount;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * إنشاء وعاء مالي (صندوق أو حساب بنكي) — وهو إنشاءٌ على خطوتين: حساب في شجرة
 * USALI ثم الوعاء المرتبط به.
 *
 * وقع على الإنتاج أن يُترك المستخدم بعض الحقول فارغة فتظهر له رسالة خطأ بينما
 * يكون حساب الشجرة قد أُنشئ فعلاً وبقي يتيماً: النموذج يُرسل الحقول الرقمية
 * الفارغة null، وأعمدتها NOT NULL، فترتدّ القاعدة بعد الخطوة الأولى — ولم تكن
 * الخطوتان في معاملة واحدة.
 */
class PaymentAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->admin = User::role('admin')->firstOrFail();
    }

    /** ما يُرسله النموذج فعلاً حين تُترك الحقول الاختيارية فارغة */
    private function blankOptionalFields(): array
    {
        return [
            'bank_name'       => null,
            'account_number'  => null,
            'iban'            => null,
            'commission_rate' => null,
            'sort_order'      => null,
            'notes'           => null,
        ];
    }

    public function test_optional_numeric_fields_left_empty_do_not_break_the_insert(): void
    {
        $this->actingAs($this->admin)
            ->post(route('payment-accounts.store'), [
                'name' => 'بنك الكريمي — الحساب الجاري',
                'type' => PaymentAccount::TYPE_BANK,
            ] + $this->blankOptionalFields())
            ->assertSessionHas('success');

        $account = PaymentAccount::where('name', 'بنك الكريمي — الحساب الجاري')->firstOrFail();

        // أعمدة NOT NULL تصل بقيمها الافتراضية لا بـnull
        $this->assertSame('0.00', (string) $account->commission_rate);
        $this->assertSame(0, $account->sort_order);
        $this->assertFalse($account->is_default);
    }

    /**
     * لا حساب يتيم: فشل الخطوة الثانية يُلغي الأولى. تُفرض الحالة بمُستمع يرمي
     * عند الإنشاء — فالاختبار يقيس العقد (الذرّية) لا خصوصية سائق قاعدة بعينه،
     * إذ يقبل sqlite ما ترفضه MySQL فلا يظهر العطل إلا على الإنتاج.
     */
    public function test_a_failure_after_the_ledger_account_leaves_nothing_behind(): void
    {
        $before = ChartOfAccount::count();

        PaymentAccount::creating(static function (): void {
            throw new \RuntimeException('فشل مُفتعَل بعد إنشاء حساب الشجرة');
        });

        try {
            $this->actingAs($this->admin)->post(route('payment-accounts.store'), [
                'name' => 'صندوق لن يُنشأ',
                'type' => PaymentAccount::TYPE_SAFE,
            ] + $this->blankOptionalFields());
        } catch (\Throwable) {
            // الخطأ متوقَّع — المقصود ما يبقى في القاعدة بعده
        }

        $this->assertSame($before, ChartOfAccount::count(), 'بقي حساب يتيم في دليل الحسابات');
        $this->assertDatabaseMissing('payment_accounts', ['name' => 'صندوق لن يُنشأ']);
    }

    /**
     * الكود المولَّد ابنٌ حقيقي لأبيه: يشاركه خاناته الأولى ويختلف في خانة
     * مستواه وحدها. الزيادة العمياء السابقة كانت تُنتج 1101 تحت 1100.
     */
    public function test_the_generated_code_is_a_real_child_of_its_parent(): void
    {
        $this->actingAs($this->admin)->post(route('payment-accounts.store'), [
            'name' => 'صندوق الاستقبال الثاني',
            'type' => PaymentAccount::TYPE_SAFE,
        ] + $this->blankOptionalFields());

        $account = PaymentAccount::where('name', 'صندوق الاستقبال الثاني')->firstOrFail();
        $ledger  = ChartOfAccount::where('code', $account->account_code)->firstOrFail();
        $parent  = $ledger->parent;

        $this->assertNotNull($parent, 'الحساب المولَّد بلا أب');
        $this->assertSame($parent->level + 1, $ledger->level);
        $this->assertSame(
            substr($parent->code, 0, $parent->level),
            substr($ledger->code, 0, $parent->level),
            'الكود المولَّد لا يقع تحت أبيه'
        );
        // والخانات بعد موضعه أصفار محجوزة لفروعه
        $this->assertSame(
            str_repeat('0', 4 - $ledger->level),
            substr($ledger->code, $ledger->level),
            'الكود المولَّد لا يترك مكاناً لفروعه'
        );
        $this->assertTrue($ledger->is_posting);
    }

    /** وأبوه يفقد قابلية الترحيل بمجرد أن يصير له فرع */
    public function test_the_parent_stops_being_a_posting_account(): void
    {
        $parentCode = '1130';   // البنك — الحساب الجاري
        ChartOfAccount::where('code', $parentCode)->update(['is_posting' => true]);

        $this->actingAs($this->admin)->post(route('payment-accounts.store'), [
            'name' => 'بنك التضامن',
            'type' => PaymentAccount::TYPE_BANK,
        ] + $this->blankOptionalFields());

        $this->assertFalse(ChartOfAccount::where('code', $parentCode)->value('is_posting'));
    }

    /** وحين تمتلئ فروع الأب تُرفض العملية برسالة مفهومة لا بكود مكسور */
    public function test_a_full_parent_branch_is_refused_with_a_clear_message(): void
    {
        $parent = ChartOfAccount::where('code', '1130')->firstOrFail();

        // إشغال الخانات التسع كلها
        for ($digit = 1; $digit <= 9; $digit++) {
            ChartOfAccount::updateOrCreate(['code' => '113' . $digit], [
                'parent_code' => '1130', 'name_ar' => "حشو {$digit}", 'name_en' => "filler {$digit}",
                'type' => 'asset', 'subtype' => 'current', 'level' => 4,
                'is_posting' => true, 'normal_balance' => 'debit', 'is_active' => true,
            ]);
        }

        $this->assertNull($parent->fresh()->nextChildCode());

        $before = ChartOfAccount::count();

        try {
            $this->actingAs($this->admin)->post(route('payment-accounts.store'), [
                'name' => 'بنك زائد',
                'type' => PaymentAccount::TYPE_BANK,
            ] + $this->blankOptionalFields());
        } catch (\Throwable $e) {
            $this->assertStringContainsString('امتلأت فروع الحساب', $e->getMessage());
        }

        $this->assertSame($before, ChartOfAccount::count());
        $this->assertDatabaseMissing('payment_accounts', ['name' => 'بنك زائد']);
    }
}
