<?php

namespace Tests\Feature;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\User;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * تحرير دليل الحسابات من الواجهة.
 *
 * الشجرة كانت للقراءة فقط، وفتحها للتحرير يفتح معه أبواب فساد صامت: حسابٌ
 * يخالف نوع أبيه فيُجمع أصلٌ تحت خصوم، أو أبٌ مُرحَّل عليه يصير تجميعياً
 * فيُحتسب رصيده مرتين، أو إيقاف حساب كوده مثبّت في شيفرة الترحيل فتتعطّل
 * دفعة نزيل بعد أسبوع. هذه الاختبارات تحرس كل باب منها.
 */
class ChartOfAccountsCrudTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ChartOfAccountsSeeder::class);
        // ذاكرة صلاحيات Spatie تصمد بين الاختبارات فتُقرأ أدوار قاعدةٍ سابقة
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = User::role('admin')->firstOrFail();
    }

    /** يُرحّل قيداً على حساب كي يصير «مستعملاً» */
    private function postLineOn(string $code): void
    {
        $entry = JournalEntry::create([
            'entry_date'  => today(),
            'description' => 'قيد اختباري',
            'source_type' => 'test',
            'source_id'   => 1,
        ]);

        JournalLine::create([
            'journal_entry_id' => $entry->id,
            'account_code'     => $code,
            'debit'            => 100,
            'credit'           => 0,
        ]);
    }

    /**
     * مستخدم يقرأ الشجرة ولا يُحرّرها.
     *
     * الصلاحية تُمنح بسجل صريح في user_permissions لأن هذا هو مصدر الحقيقة في
     * PermissionService — وهو ما يُستشار أولاً في Gate::before.
     */
    private function viewerWithoutManage(): User
    {
        $user = User::role('receptionist')->firstOrFail();

        \App\Models\UserPermission::updateOrCreate(
            ['user_id' => $user->id, 'permission_key' => 'reports.view'],
            ['is_granted' => true, 'granted_at' => now()]
        );

        return $user->fresh();
    }

    // ───────────────── الصلاحية ─────────────────

    /** من يرى التقارير يقرأ الشجرة ولا يُحرّرها */
    public function test_a_viewer_without_manage_permission_cannot_write(): void
    {
        $viewer = $this->viewerWithoutManage();

        $this->actingAs($viewer)
            ->post(route('coa.store'), ['parent_code' => '1100', 'code' => '1190', 'name_ar' => 'اختبار'])
            ->assertRedirect();

        $this->assertDatabaseMissing('chart_of_accounts', ['code' => '1190']);
    }

    // ───────────────── الإضافة ─────────────────

    public function test_a_new_leaf_is_created_under_its_parent(): void
    {
        $this->actingAs($this->admin)
            ->post(route('coa.store'), [
                'parent_code' => '1120',
                'code'        => '1123',
                'name_ar'     => 'صندوق الطوارئ',
                'notes'       => 'يُفتح عند الحاجة',
            ])
            ->assertRedirect();

        $account = ChartOfAccount::where('code', '1123')->firstOrFail();

        $this->assertSame('1120', $account->parent_code);
        $this->assertSame('صندوق الطوارئ', $account->name_ar);
        // النوع والطبيعة من الأب لا من النموذج
        $this->assertSame('asset', $account->type);
        $this->assertSame('debit', $account->normal_balance);
        $this->assertSame(4, $account->level);
        $this->assertTrue($account->is_posting);
        $this->assertFalse($account->is_system, 'الحساب اليدوي يجب ألا يُوسم أساسياً');
        // الاسم الإنجليزي الفارغ يُنسخ من العربي كي لا يظهر بلا اسم في التصدير
        $this->assertSame('صندوق الطوارئ', $account->name_en);
    }

    /** الأب يفقد قابلية الترحيل بمجرد أن يصير له فرع، وإلا حُسب رصيده مرتين */
    public function test_the_parent_stops_being_a_posting_account(): void
    {
        $parent = ChartOfAccount::where('code', '1120')->firstOrFail();
        $this->assertTrue($parent->is_posting, 'الحساب المختار للاختبار يجب أن يكون ورقة');

        $this->actingAs($this->admin)->post(route('coa.store'), [
            'parent_code' => '1120',
            'code'        => '1123',
            'name_ar'     => 'صندوق الطوارئ',
        ]);

        $this->assertFalse($parent->fresh()->is_posting);
    }

    /** ولا يصير أباً حسابٌ رُحِّلت عليه قيود: رصيده القديم سيُجمع مع فروعه */
    public function test_an_account_with_journal_lines_cannot_become_a_parent(): void
    {
        // 1120 «الصندوق العام»: ورقة في المستوى الثالث تصلح أباً لولا القيد
        $this->postLineOn('1120');

        $this->actingAs($this->admin)
            ->post(route('coa.store'), [
                'parent_code' => '1120',
                'code'        => '1123',
                'name_ar'     => 'صندوق فرعي',
            ])
            ->assertSessionHasErrors('parent_code');

        $this->assertDatabaseMissing('chart_of_accounts', ['code' => '1123']);
    }

    public function test_a_code_outside_the_parents_branch_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post(route('coa.store'), [
                'parent_code' => '1100',
                'code'        => '2170',   // فرع الخصوم لا النقدية
                'name_ar'     => 'حساب في غير موضعه',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_a_code_that_leaves_no_room_for_its_own_children_is_refused(): void
    {
        // ابن 1000 (مستوى ١) يجب أن يكون X X 0 0 — و1123 يحجز خانتين لنفسه
        $this->actingAs($this->admin)
            ->post(route('coa.store'), [
                'parent_code' => '1000',
                'code'        => '1123',
                'name_ar'     => 'حساب بترقيم مكسور',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_a_duplicate_code_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->post(route('coa.store'), [
                'parent_code' => '1100',
                'code'        => '1120',
                'name_ar'     => 'مكرر',
            ])
            ->assertSessionHasErrors('code');
    }

    /** الرقم المقترح يتبع موضع الحساب في الشجرة لا عدّاداً */
    public function test_the_suggested_code_follows_the_tree_position(): void
    {
        $parent = ChartOfAccount::where('code', '1120')->firstOrFail();
        $this->assertSame('1121', $parent->nextChildCode());

        ChartOfAccount::create([
            'code' => '1121', 'parent_code' => '1120', 'name_ar' => 'أ', 'name_en' => 'a',
            'type' => 'asset', 'subtype' => 'current', 'level' => 4, 'is_posting' => true,
            'normal_balance' => 'debit', 'is_active' => true,
        ]);

        $this->assertSame('1122', $parent->fresh()->nextChildCode());

        // حساب المستوى الرابع لا يقبل فروعاً
        $this->assertNull(ChartOfAccount::where('code', '1121')->first()->nextChildCode());
    }

    // ───────────────── التعديل ─────────────────

    public function test_renaming_works_and_structure_is_never_taken_from_the_form(): void
    {
        $this->actingAs($this->admin)
            ->put(route('coa.update', '1120'), [
                'name_ar' => 'الخزنة الرئيسية',
                'notes'   => 'بعهدة المدير',
                // محاولة تمرير بنية — يجب أن تُتجاهل تماماً
                'code'        => '9999',
                'type'        => 'liability',
                'parent_code' => '2000',
                'level'       => 1,
            ])
            ->assertRedirect();

        $account = ChartOfAccount::where('code', '1120')->firstOrFail();

        $this->assertSame('الخزنة الرئيسية', $account->name_ar);
        $this->assertSame('بعهدة المدير', $account->notes);
        $this->assertSame('asset', $account->type);
        $this->assertSame('1100', $account->parent_code);
        $this->assertSame(3, $account->level);
        $this->assertDatabaseMissing('chart_of_accounts', ['code' => '9999']);
    }

    /**
     * إيقاف حسابٍ كوده مثبّت في شيفرة الترحيل ممنوع: العطل لا يظهر لحظة
     * الإيقاف بل لحظة تسجيل دفعة لنزيل حقيقي بعد أيام.
     */
    public function test_an_account_used_by_the_posting_code_cannot_be_suspended(): void
    {
        $this->actingAs($this->admin)
            ->put(route('coa.update', '4110'), ['name_ar' => 'إيراد السعر المعلن', 'suspended' => 1])
            ->assertSessionHas('error');

        $this->assertTrue(ChartOfAccount::where('code', '4110')->value('is_active'));
    }

    public function test_an_unused_account_can_be_suspended_and_restored(): void
    {
        // حسابٌ لا تستعمله شيفرة الترحيل ولا هو حساب وعاءٍ مالي نشط
        $code = ChartOfAccount::where('is_posting', true)
            ->whereNotIn('code', app(\App\Services\COAService::class)->hardcodedPostingCodes())
            ->whereNotIn('code', \App\Models\PaymentAccount::pluck('account_code'))
            ->value('code');

        $this->assertNotNull($code, 'لم يُوجد حساب صالح للاختبار');

        $this->actingAs($this->admin)
            ->put(route('coa.update', $code), ['name_ar' => 'اسم', 'suspended' => 1])
            ->assertSessionMissing('error');
        $this->assertFalse(ChartOfAccount::where('code', $code)->value('is_active'));

        $this->actingAs($this->admin)->put(route('coa.update', $code), ['name_ar' => 'اسم']);
        $this->assertTrue(ChartOfAccount::where('code', $code)->value('is_active'));
    }

    // ───────────────── الحسابات الأساسية التجميعية ─────────────────

    /**
     * عظام الشجرة (حساب من البذرة وله فروع) تُقرأ ولا تُكتب: اسمه عنوان سطرٍ
     * في الميزانية وقائمة الدخل، ورصيده حاصل جمع فروعه لا رصيد خاص به.
     */
    public function test_a_structural_parent_account_cannot_be_renamed(): void
    {
        $this->actingAs($this->admin)
            ->put(route('coa.update', '1000'), ['name_ar' => 'الموجودات'])
            ->assertSessionHas('error');

        $this->assertSame('الأصول', ChartOfAccount::where('code', '1000')->value('name_ar'));
    }

    public function test_a_structural_parent_account_cannot_be_suspended(): void
    {
        $this->actingAs($this->admin)
            ->put(route('coa.update', '1100'), ['name_ar' => 'النقدية والبنوك', 'suspended' => 1])
            ->assertSessionHas('error');

        $this->assertTrue(ChartOfAccount::where('code', '1100')->value('is_active'));
    }

    public function test_a_structural_parent_account_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('coa.destroy', '1100'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('chart_of_accounts', ['code' => '1100']);
    }

    /** لكن الإضافة تحته مفتوحة: هي لا تمسّ بنيته */
    public function test_a_child_may_still_be_added_under_a_locked_parent(): void
    {
        $this->actingAs($this->admin)
            ->post(route('coa.store'), [
                'parent_code' => '1100',
                'code'        => '1190',
                'name_ar'     => 'نقدية أخرى',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('chart_of_accounts', ['code' => '1190']);
    }

    /** وورقة البذرة تبقى قابلة لإعادة التسمية — القفل على البنية لا على كل مبذور */
    public function test_a_seeded_leaf_is_still_renameable(): void
    {
        $this->actingAs($this->admin)
            ->put(route('coa.update', '1120'), ['name_ar' => 'الخزنة الرئيسية'])
            ->assertSessionMissing('error');

        $this->assertSame('الخزنة الرئيسية', ChartOfAccount::where('code', '1120')->value('name_ar'));
    }

    /** والقفل يظهر في الواجهة قبل الضغط لا بعده */
    public function test_the_form_shows_a_locked_parent_as_read_only(): void
    {
        $this->actingAs($this->admin)
            ->get(route('coa.index', ['edit' => '1000']))
            ->assertOk()
            ->assertSee('حساب أساسي في بنية شجرة USALI', false)
            ->assertSee('محميّ', false);
    }

    // ───────────────── الحذف ─────────────────

    public function test_a_user_added_unused_account_is_deletable(): void
    {
        $this->actingAs($this->admin)->post(route('coa.store'), [
            'parent_code' => '1120', 'code' => '1123', 'name_ar' => 'مؤقت',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('coa.destroy', '1123'))
            ->assertRedirect();

        $this->assertDatabaseMissing('chart_of_accounts', ['code' => '1123']);
        // وعاد الأب ورقةً قابلة للترحيل بعد رحيل آخر فروعه
        $this->assertTrue(ChartOfAccount::where('code', '1120')->value('is_posting'));
    }

    public function test_a_seeded_account_is_never_deletable(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('coa.destroy', '1120'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('chart_of_accounts', ['code' => '1120']);
    }

    public function test_an_account_with_journal_lines_is_never_deletable(): void
    {
        $this->actingAs($this->admin)->post(route('coa.store'), [
            'parent_code' => '1120', 'code' => '1123', 'name_ar' => 'مؤقت',
        ]);
        $this->postLineOn('1123');

        $this->actingAs($this->admin)
            ->delete(route('coa.destroy', '1123'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('chart_of_accounts', ['code' => '1123']);
    }

    // ───────────────── صمود التعديلات أمام البذرة ─────────────────

    /**
     * أخطر ما في فتح التحرير: البذرة تعمل في كل نشر، فلو أعادت كتابة ما
     * يُدخله المستخدم لاختفى تعديله بعد أيام دون أن يدري بالسبب.
     */
    public function test_user_edits_survive_a_reseed(): void
    {
        // التسمية أولاً ثم الفرع: بعد أن يصير 1120 أباً يُقفل فلا يقبل تعديلاً
        $this->actingAs($this->admin)->put(route('coa.update', '1120'), [
            'name_ar' => 'الخزنة الرئيسية', 'notes' => 'بعهدة المدير',
        ]);
        $this->actingAs($this->admin)->post(route('coa.store'), [
            'parent_code' => '1120', 'code' => '1123', 'name_ar' => 'صندوق الطوارئ',
        ]);

        $this->seed(ChartOfAccountsSeeder::class);

        $renamed = ChartOfAccount::where('code', '1120')->firstOrFail();
        $this->assertSame('الخزنة الرئيسية', $renamed->name_ar, 'البذرة محت تسمية المستخدم');
        $this->assertSame('بعهدة المدير', $renamed->notes);
        // والحساب المُضاف يدوياً لم تمسسه البذرة ولم تُعد أباه قابلاً للترحيل
        $this->assertDatabaseHas('chart_of_accounts', ['code' => '1123']);
        $this->assertFalse($renamed->is_posting, 'البذرة أعادت الأب قابلاً للترحيل فيُحتسب رصيده مرتين');
    }

    // ───────────────── الصفحة ─────────────────

    public function test_the_page_shows_the_editor_only_to_those_who_may_edit(): void
    {
        $this->actingAs($this->admin)
            ->get(route('coa.index', ['edit' => '1120']))
            ->assertOk()
            ->assertSee('التحرير', false)
            ->assertSee('إيقاف الحساب', false);

        $viewer = $this->viewerWithoutManage();

        $this->actingAs($viewer)
            ->get(route('coa.index'))
            ->assertOk()
            ->assertDontSee('إيقاف الحساب', false);
    }

    public function test_the_new_account_form_suggests_a_code_under_the_chosen_parent(): void
    {
        $this->actingAs($this->admin)
            ->get(route('coa.index', ['new' => 1, 'parent' => '1120']))
            ->assertOk()
            ->assertSee('1121', false);
    }
}
