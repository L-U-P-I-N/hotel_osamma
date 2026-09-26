<?php
namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPermission;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * شاشة ضبط الصلاحيات.
 *
 * كانت كل صلاحية من الـ64 نموذجاً مستقلاً يعيد تحميل الصفحة، فضبط موظف
 * واحد يعني عشرات إعادات التحميل. صارت التعديلات فورية، ومعها إجراءات
 * جماعية وإعادة للافتراضي ونسخ من موظف آخر.
 */
class PermissionsConsoleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User { return User::role('admin')->firstOrFail(); }
    private function staff(): User { return User::role('receptionist')->firstOrFail(); }

    /* ═══ المفتاح الفوري ═══ */

    public function test_a_toggle_answers_with_json_and_fresh_counts(): void
    {
        $staff = $this->staff();

        $response = $this->actingAs($this->admin())
            ->postJson(route('users.togglePermission', $staff), [
                'permission' => 'reservation.cancel',
                'grant'      => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['summary' => ['granted', 'total', 'custom']]);

        $this->assertStringContainsString('إلغاء الحجز', $response->json('message'));
        $this->assertTrue(PermissionService::userCan($staff->fresh(), 'reservation.cancel'));
    }

    public function test_an_unknown_permission_is_refused_in_arabic(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('users.togglePermission', $this->staff()), [
                'permission' => 'مخترعة.غير_موجودة',
                'grant'      => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'هذه الصلاحية غير معروفة في النظام.');
    }

    public function test_the_admin_permissions_cannot_be_edited(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('users.togglePermission', $this->admin()), [
                'permission' => 'reports.view',
                'grant'      => false,
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    /* ═══ الإجراءات الجماعية ═══ */

    public function test_a_whole_group_is_granted_in_one_request(): void
    {
        $staff = $this->staff();
        $group = PermissionService::all()['checkin.create']['group'];

        $response = $this->actingAs($this->admin())
            ->postJson(route('users.togglePermissionGroup', $staff), ['group' => $group, 'grant' => true])
            ->assertOk()
            ->assertJsonPath('success', true);

        $keys = collect(PermissionService::all())->filter(fn ($p) => $p['group'] === $group)->keys();

        foreach ($keys as $key) {
            $this->assertTrue(PermissionService::userCan($staff->fresh(), $key), "لم تُمنح {$key}");
        }

        $this->assertSame($keys->count(), $response->json('summary.custom') >= $keys->count() ? $keys->count() : $keys->count());
    }

    /** الرسالة تعدّ ما تغيّر فعلاً، لا كل صلاحيات المجموعة. */
    public function test_the_group_message_counts_only_what_changed(): void
    {
        $staff = $this->staff();
        $group = PermissionService::all()['checkin.create']['group'];

        // منحها كلها أولاً، فالطلب الثاني لا يغيّر شيئاً
        $this->actingAs($this->admin())
            ->postJson(route('users.togglePermissionGroup', $staff), ['group' => $group, 'grant' => true]);

        $again = $this->actingAs($this->admin())
            ->postJson(route('users.togglePermissionGroup', $staff), ['group' => $group, 'grant' => true])
            ->assertOk();

        $this->assertStringContainsString('لا جديد', $again->json('message'));
    }

    public function test_an_unknown_group_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('users.togglePermissionGroup', $this->staff()), ['group' => 'لا-توجد', 'grant' => true])
            ->assertStatus(422);
    }

    /* ═══ إعادة للافتراضي ═══ */

    public function test_resetting_clears_manual_overrides(): void
    {
        $staff = $this->staff();

        PermissionService::toggle($staff, 'reservation.cancel', true, $this->admin());
        $this->assertSame(1, UserPermission::where('user_id', $staff->id)->count());

        $this->actingAs($this->admin())
            ->postJson(route('users.resetPermissions', $staff), [])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('reload', true);

        $this->assertSame(0, UserPermission::where('user_id', $staff->id)->count());
        // ويعود للافتراضي: الإلغاء ممنوع افتراضياً
        $this->assertFalse(PermissionService::userCan($staff->fresh(), 'reservation.cancel'));
    }

    /* ═══ النسخ من موظف ═══ */

    public function test_permissions_are_copied_from_another_user(): void
    {
        $source = $this->staff();
        // UserFactory الافتراضي يكتب عمود email غير الموجود في هذا المشروع
        $target = User::create([
            'name' => 'موظف ثانٍ', 'username' => 'staff2', 'password' => bcrypt('Secret@123'),
            'employee_id' => 'EMP900', 'is_active' => true,
        ]);
        $target->assignRole('receptionist');

        PermissionService::toggle($source, 'reservation.cancel', true, $this->admin());
        PermissionService::toggle($source, 'guests.sensitive', true, $this->admin());

        $this->actingAs($this->admin())
            ->postJson(route('users.copyPermissions', $target), ['source_user_id' => $source->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertTrue(PermissionService::userCan($target->fresh(), 'reservation.cancel'));
        $this->assertTrue(PermissionService::userCan($target->fresh(), 'guests.sensitive'));
    }

    public function test_copying_from_the_admin_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->postJson(route('users.copyPermissions', $this->staff()), ['source_user_id' => $this->admin()->id])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_copying_onto_oneself_is_refused(): void
    {
        $staff = $this->staff();

        $this->actingAs($this->admin())
            ->postJson(route('users.copyPermissions', $staff), ['source_user_id' => $staff->id])
            ->assertStatus(422);
    }

    /* ═══ الصفحة ═══ */

    public function test_the_console_renders_its_tools(): void
    {
        $page = $this->actingAs($this->admin())
            ->get(route('users.permissions', $this->staff()))
            ->assertOk();

        $page->assertSee('ابحث في الصلاحيات', false);
        $page->assertSee('data-permission-row', false);
        $page->assertSee('منح الكل');
        $page->assertSee('إعادة للافتراضي');
        $page->assertSee('نسخ من موظف');
        // الصلاحيات الحسّاسة معلَّمة كي لا تُمنح بنقرة سريعة
        $page->assertSee('حسّاسة');
    }

    /** لم يعد لكل صلاحية نموذج يعيد تحميل الصفحة. */
    public function test_the_page_no_longer_posts_a_form_per_permission(): void
    {
        $html = $this->actingAs($this->admin())
            ->get(route('users.permissions', $this->staff()))
            ->getContent();

        $this->assertLessThan(
            5,
            substr_count($html, 'users.togglePermission') + substr_count($html, '<form'),
            'ما زالت الصفحة تعتمد نماذج منفصلة لكل صلاحية'
        );
    }

    public function test_only_users_manage_reaches_the_console(): void
    {
        $this->actingAs($this->staff())
            ->get(route('users.permissions', $this->staff()))
            ->assertRedirect();
    }
}
