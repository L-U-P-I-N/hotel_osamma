<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\User;
use App\Services\COAService;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * سلامة شجرة الحسابات: البنية، الرصيد الطبيعي، وتوازن القيود.
 */
class ChartOfAccountsTest extends TestCase
{
    use RefreshDatabase;

    private COAService $coa;

    /** الأدوار والمستخدمون لازمون لاختبارات الواجهات */
    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
        $this->coa = app(COAService::class);
    }

    // ───────────────── سلامة الشجرة ─────────────────

    public function test_seeder_creates_all_five_groups_with_expected_root_codes(): void
    {
        $roots = ChartOfAccount::roots()->orderBy('code')->pluck('code')->all();

        $this->assertSame(['1000', '2000', '3000', '4000', '5000', '6000'], $roots);

        $this->assertSame('asset',     ChartOfAccount::where('code', '1000')->value('type'));
        $this->assertSame('liability', ChartOfAccount::where('code', '2000')->value('type'));
        $this->assertSame('equity',    ChartOfAccount::where('code', '3000')->value('type'));
        $this->assertSame('revenue',   ChartOfAccount::where('code', '4000')->value('type'));
        $this->assertSame('expense',   ChartOfAccount::where('code', '5000')->value('type'));
        $this->assertSame('expense',   ChartOfAccount::where('code', '6000')->value('type'));
    }

    public function test_seeder_meets_the_minimum_account_count(): void
    {
        $this->assertGreaterThanOrEqual(80, ChartOfAccount::count());
    }

    /** لا حساب يتيم: كل parent_code موجود فعلاً */
    public function test_tree_has_no_orphan_accounts(): void
    {
        $codes = ChartOfAccount::pluck('code');

        $orphans = ChartOfAccount::whereNotNull('parent_code')
            ->whereNotIn('parent_code', $codes)
            ->pluck('code')
            ->all();

        $this->assertSame([], $orphans, 'حسابات بلا أب: ' . implode(', ', $orphans));
    }

    public function test_service_reports_a_healthy_tree(): void
    {
        $this->assertSame([], $this->coa->findIntegrityIssues());
    }

    /** عمق كل حساب = عمق أبيه + 1، ونوعه يطابق نوع أبيه */
    public function test_levels_and_types_are_consistent_with_parents(): void
    {
        $byCode = ChartOfAccount::all()->keyBy('code');

        foreach ($byCode as $account) {
            if ($account->parent_code === null) {
                $this->assertSame(1, $account->level, "الجذر {$account->code} يجب أن يكون مستوى 1");
                continue;
            }

            $parent = $byCode[$account->parent_code];

            $this->assertSame($parent->level + 1, $account->level,
                "مستوى {$account->code} لا يساوي مستوى أبيه + 1");
            $this->assertSame($parent->type, $account->type,
                "نوع {$account->code} يخالف نوع أبيه {$parent->code}");
        }
    }

    /** الترحيل على الأوراق فقط، ولا حساب أب مفتوح للترحيل */
    public function test_posting_is_restricted_to_leaf_accounts(): void
    {
        $this->assertSame(0, ChartOfAccount::where('is_posting', true)->where('level', '<', 3)->count());

        $parentCodes = ChartOfAccount::whereNotNull('parent_code')->distinct()->pluck('parent_code');

        $this->assertSame(0, ChartOfAccount::whereIn('code', $parentCodes)->where('is_posting', true)->count(),
            'حساب أب مفتوح للترحيل');
    }

    /** البذرة عديمة الأثر التراكمي */
    public function test_seeder_is_idempotent(): void
    {
        $before = ChartOfAccount::count();

        $this->seed(ChartOfAccountsSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $this->assertSame($before, ChartOfAccount::count());
    }

    // ───────────────── الرصيد الطبيعي ─────────────────

    public function test_normal_balance_matches_account_nature(): void
    {
        foreach (ChartOfAccount::all() as $account) {
            $this->assertSame(
                $account->getNormalBalance(),
                $account->normal_balance,
                "الرصيد الطبيعي للحساب {$account->code} غير صحيح"
            );
        }

        // عيّنات صريحة بأكوادها
        $this->assertSame('debit',  ChartOfAccount::where('code', '1120')->value('normal_balance')); // الصندوق العام
        $this->assertSame('credit', ChartOfAccount::where('code', '2310')->value('normal_balance')); // ضريبة مستحقة
        $this->assertSame('credit', ChartOfAccount::where('code', '3110')->value('normal_balance')); // رأس المال
        $this->assertSame('credit', ChartOfAccount::where('code', '4110')->value('normal_balance')); // إيراد الغرف
        $this->assertSame('debit',  ChartOfAccount::where('code', '6410')->value('normal_balance')); // الكهرباء
    }

    /** الحسابات المقابلة تعكس رصيد نوعها */
    public function test_contra_accounts_invert_their_type_balance(): void
    {
        // مجمّع إهلاك المباني: أصل برصيد دائن
        $accum = ChartOfAccount::where('code', '1620')->firstOrFail();
        $this->assertSame('asset', $accum->type);
        $this->assertSame('contra_asset', $accum->subtype);
        $this->assertSame('credit', $accum->normal_balance);

        // خصومات الغرف: إيراد برصيد مدين
        $rebate = ChartOfAccount::where('code', '4190')->firstOrFail();
        $this->assertSame('revenue', $rebate->type);
        $this->assertSame('debit', $rebate->normal_balance);
    }

    /** الرصيد الطبيعي يُشتق عند الحفظ ولا يُقبل يدوياً */
    public function test_model_overrides_a_wrong_normal_balance_on_save(): void
    {
        $account = ChartOfAccount::create([
            'code' => '9910', 'parent_code' => null,
            'name_en' => 'Test Revenue', 'name_ar' => 'إيراد اختبار',
            'type' => 'revenue', 'subtype' => 'operating',
            'normal_balance' => 'debit', // خطأ متعمّد
            'level' => 1, 'is_posting' => true, // خطأ متعمّد: مستوى 1 لا يُرحَّل
        ]);

        $this->assertSame('credit', $account->fresh()->normal_balance);
        $this->assertFalse($account->fresh()->is_posting);
    }

    public function test_signed_amount_follows_the_normal_balance(): void
    {
        $cash    = ChartOfAccount::where('code', '1120')->firstOrFail(); // مدين
        $revenue = ChartOfAccount::where('code', '4110')->firstOrFail(); // دائن

        $this->assertEqualsWithDelta(100.0,  $cash->signedAmount(100, 0), 0.01);
        $this->assertEqualsWithDelta(-100.0, $cash->signedAmount(0, 100), 0.01);
        $this->assertEqualsWithDelta(100.0,  $revenue->signedAmount(0, 100), 0.01);
        $this->assertEqualsWithDelta(-100.0, $revenue->signedAmount(100, 0), 0.01);
    }

    // ───────────────── الخدمة ─────────────────

    public function test_build_tree_nests_children_under_their_parents(): void
    {
        $tree = $this->coa->buildTree();

        $this->assertCount(6, $tree);

        $assets = collect($tree)->firstWhere('code', '1000');
        $this->assertNotNull($assets);

        $cash = collect($assets['children'])->firstWhere('code', '1100');
        $this->assertNotNull($cash, '1100 يجب أن يكون ابناً لـ1000');

        $float = collect($cash['children'])->firstWhere('code', '1110');
        $this->assertNotNull($float, '1110 يجب أن يكون ابناً لـ1100');

        $drawer = collect($float['children'])->firstWhere('code', '1111');
        $this->assertNotNull($drawer, '1111 يجب أن يكون ابناً لـ1110 (عمق 4)');
    }

    public function test_build_tree_respects_type_filter(): void
    {
        $tree = $this->coa->buildTree(['type' => 'revenue']);

        $this->assertCount(1, $tree);
        $this->assertSame('4000', $tree[0]['code']);
    }

    public function test_get_posting_accounts_filters_by_type_and_department(): void
    {
        $all = $this->coa->getPostingAccounts();
        $this->assertTrue($all->every(fn (ChartOfAccount $a) => $a->is_posting && $a->is_active));

        $rooms = $this->coa->getPostingAccounts('revenue', 'rooms');
        $this->assertTrue($rooms->every(fn ($a) => $a->type === 'revenue' && $a->department === 'rooms'));
        $this->assertTrue($rooms->contains('code', '4110'));
        $this->assertFalse($rooms->contains('code', '4210')); // إيراد الأطعمة ليس قسم الغرف
    }

    public function test_get_posting_accounts_rejects_an_unknown_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->coa->getPostingAccounts('not_a_type');
    }

    public function test_scope_by_department_returns_only_that_department(): void
    {
        $fnb = ChartOfAccount::byDepartment('fnb')->get();

        $this->assertGreaterThan(0, $fnb->count());
        $this->assertTrue($fnb->every(fn ($a) => $a->department === 'fnb'));
    }

    public function test_leaf_codes_under_a_parent_are_all_posting_accounts(): void
    {
        $leaves = $this->coa->getLeafCodesUnder('4100');

        $this->assertContains('4110', $leaves);
        $this->assertContains('4190', $leaves);
        $this->assertNotContains('4100', $leaves, 'الأب التجميعي لا يكون ورقة');
    }

    public function test_account_path_walks_from_the_root(): void
    {
        $path = $this->coa->getAccountPath('1111');

        $this->assertStringContainsString('الأصول', $path);
        $this->assertStringContainsString('النقدية والبنوك', $path);
        $this->assertStringContainsString('درج نقدية الوردية', $path);
    }

    // ───────────────── توازن القيود ─────────────────

    public function test_balanced_entry_passes_validation(): void
    {
        $result = $this->coa->validateJournalEntry([
            ['account_code' => '1120', 'debit' => 35000, 'credit' => 0],     // الصندوق العام
            ['account_code' => '4110', 'debit' => 0,     'credit' => 35000], // إيراد الغرف
        ]);

        $this->assertTrue($result['valid'], implode(' | ', $result['errors']));
        $this->assertEqualsWithDelta(35000, $result['total_debit'], 0.01);
        $this->assertEqualsWithDelta(35000, $result['total_credit'], 0.01);
        $this->assertEqualsWithDelta(0, $result['difference'], 0.01);
    }

    public function test_unbalanced_entry_is_rejected(): void
    {
        $result = $this->coa->validateJournalEntry([
            ['account_code' => '1120', 'debit' => 35000, 'credit' => 0],
            ['account_code' => '4110', 'debit' => 0,     'credit' => 30000],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertEqualsWithDelta(5000, $result['difference'], 0.01);
        $this->assertStringContainsString('غير متوازن', implode(' ', $result['errors']));
    }

    public function test_entry_on_a_non_posting_parent_account_is_rejected(): void
    {
        $result = $this->coa->validateJournalEntry([
            ['account_code' => '1100', 'debit' => 100, 'credit' => 0], // تجميعي
            ['account_code' => '4110', 'debit' => 0,   'credit' => 100],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('تجميعي', implode(' ', $result['errors']));
    }

    public function test_entry_with_an_unknown_account_is_rejected(): void
    {
        $result = $this->coa->validateJournalEntry([
            ['account_code' => '9999', 'debit' => 100, 'credit' => 0],
            ['account_code' => '4110', 'debit' => 0,   'credit' => 100],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('غير موجود', implode(' ', $result['errors']));
    }

    public function test_entry_with_an_inactive_account_is_rejected(): void
    {
        ChartOfAccount::where('code', '4130')->update(['is_active' => false]);

        $result = $this->coa->validateJournalEntry([
            ['account_code' => '1120', 'debit' => 100, 'credit' => 0],
            ['account_code' => '4130', 'debit' => 0,   'credit' => 100],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('موقوف', implode(' ', $result['errors']));
    }

    public function test_line_cannot_be_both_debit_and_credit(): void
    {
        $result = $this->coa->validateJournalEntry([
            ['account_code' => '1120', 'debit' => 100, 'credit' => 50],
            ['account_code' => '4110', 'debit' => 0,   'credit' => 50],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('مديناً ودائناً معاً', implode(' ', $result['errors']));
    }

    public function test_single_line_entry_is_rejected(): void
    {
        $result = $this->coa->validateJournalEntry([
            ['account_code' => '1120', 'debit' => 100, 'credit' => 0],
        ]);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('سطرين على الأقل', implode(' ', $result['errors']));
    }

    /** فروق التقريب تحت حد التسامح لا تُعتبر عدم توازن */
    public function test_rounding_noise_within_tolerance_is_accepted(): void
    {
        $result = $this->coa->validateJournalEntry([
            ['account_code' => '1120', 'debit' => 100.00, 'credit' => 0],
            ['account_code' => '4110', 'debit' => 0,      'credit' => 100.004],
        ]);

        $this->assertTrue($result['valid'], implode(' | ', $result['errors']));
    }

    public function test_multi_line_balanced_entry_passes(): void
    {
        // إيراد غرف + أطعمة مقابل نقدية وذمة نزيل
        $result = $this->coa->validateJournalEntry([
            ['account_code' => '1120', 'debit' => 20000, 'credit' => 0],
            ['account_code' => '1210', 'debit' => 15000, 'credit' => 0],
            ['account_code' => '4110', 'debit' => 0,     'credit' => 30000],
            ['account_code' => '4210', 'debit' => 0,     'credit' => 5000],
        ]);

        $this->assertTrue($result['valid'], implode(' | ', $result['errors']));
        $this->assertEqualsWithDelta(35000, $result['total_debit'], 0.01);
    }

    // ───────────────── الواجهات ─────────────────

    public function test_tree_page_renders_for_a_permitted_user(): void
    {
        $admin = User::role('admin')->firstOrFail();

        $this->actingAs($admin)->get('/accounting/chart-of-accounts')
            ->assertOk()
            ->assertSee('شجرة الحسابات', false)
            ->assertSee('1000', false)
            ->assertSee('إيرادات الغرف', false)
            ->assertSee('Rooms Revenue', false);
    }

    public function test_tree_json_endpoint_returns_nested_children(): void
    {
        $admin = User::role('admin')->firstOrFail();

        $response = $this->actingAs($admin)->getJson('/accounting/chart-of-accounts/tree');

        $response->assertOk()->assertJsonPath('currency', config('hotel.base_currency'));

        $data   = $response->json('data');
        $assets = collect($data)->firstWhere('code', '1000');

        $this->assertNotNull($assets);
        $this->assertNotEmpty($assets['children']);
    }

    public function test_show_endpoint_returns_the_account_resource_with_children(): void
    {
        $admin = User::role('admin')->firstOrFail();

        $this->actingAs($admin)->getJson('/accounting/chart-of-accounts/1100')
            ->assertOk()
            ->assertJsonPath('data.code', '1100')
            ->assertJsonPath('data.name.ar', 'النقدية والبنوك')
            ->assertJsonPath('data.name.en', 'Cash & Bank')
            ->assertJsonPath('data.normal_balance', 'debit')
            ->assertJsonStructure(['data' => ['children'], 'path']);
    }

    public function test_validate_entry_endpoint_returns_422_when_unbalanced(): void
    {
        $admin = User::role('admin')->firstOrFail();

        $this->actingAs($admin)->postJson('/accounting/chart-of-accounts/validate-entry', [
            'lines' => [
                ['account_code' => '1120', 'debit' => 100, 'credit' => 0],
                ['account_code' => '4110', 'debit' => 0,   'credit' => 90],
            ],
        ])->assertStatus(422)->assertJsonPath('valid', false);
    }

    public function test_integrity_endpoint_reports_a_healthy_tree(): void
    {
        $admin = User::role('admin')->firstOrFail();

        $this->actingAs($admin)->getJson('/accounting/chart-of-accounts/integrity')
            ->assertOk()
            ->assertJsonPath('healthy', true);
    }
}
