<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingsAccountCredentialsTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function admin(): User
    {
        $admin = User::role('admin')->firstOrFail();
        $admin->forceFill(['password' => Hash::make('secret-current')])->save();

        return $admin->fresh();
    }

    public function test_settings_page_shows_the_credentials_form(): void
    {
        $this->actingAs($this->admin())
            ->get('/settings')
            ->assertOk()
            ->assertSee('بيانات الدخول', false)
            ->assertSee('كلمة المرور الحالية', false);
    }

    public function test_admin_can_change_username_and_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/settings/account', [
            'current_password'      => 'secret-current',
            'username'              => 'new-admin',
            'password'              => 'brand-new-pass',
            'password_confirmation' => 'brand-new-pass',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $admin->refresh();
        $this->assertSame('new-admin', $admin->username);
        $this->assertTrue(Hash::check('brand-new-pass', $admin->password));

        // البيانات الجديدة تعمل فعلاً في تسجيل الدخول
        auth()->logout();
        $this->post('/login', ['username' => 'new-admin', 'password' => 'brand-new-pass'])
            ->assertRedirect();
        $this->assertAuthenticatedAs($admin);
    }

    public function test_username_only_change_keeps_the_password(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/settings/account', [
            'current_password' => 'secret-current',
            'username'         => 'renamed-only',
        ])->assertSessionHasNoErrors();

        $admin->refresh();
        $this->assertSame('renamed-only', $admin->username);
        $this->assertTrue(Hash::check('secret-current', $admin->password), 'كلمة المرور يجب أن تبقى كما هي');
    }

    public function test_wrong_current_password_changes_nothing(): void
    {
        $admin = $this->admin();
        $originalUsername = $admin->username;

        $this->actingAs($admin)->post('/settings/account', [
            'current_password'      => 'wrong-password',
            'username'              => 'hijacked',
            'password'              => 'hijacked-pass',
            'password_confirmation' => 'hijacked-pass',
        ])->assertSessionHasErrors('current_password');

        $admin->refresh();
        $this->assertSame($originalUsername, $admin->username);
        $this->assertTrue(Hash::check('secret-current', $admin->password));
    }

    public function test_username_must_be_unique_and_password_confirmed(): void
    {
        $admin = $this->admin();
        $other = User::where('id', '!=', $admin->id)->firstOrFail();

        $this->actingAs($admin)->post('/settings/account', [
            'current_password' => 'secret-current',
            'username'         => $other->username,
        ])->assertSessionHasErrors('username');

        $this->actingAs($admin)->post('/settings/account', [
            'current_password'      => 'secret-current',
            'username'              => $admin->username,
            'password'              => 'password-one',
            'password_confirmation' => 'password-two',
        ])->assertSessionHasErrors('password');

        // اسم بمسافات/رموز مرفوض
        $this->actingAs($admin)->post('/settings/account', [
            'current_password' => 'secret-current',
            'username'         => 'has spaces',
        ])->assertSessionHasErrors('username');

        $this->assertNotSame('has spaces', $admin->fresh()->username);
    }

    /** اسم مستخدم يشغله حساب محذوف حذفاً ناعماً: يُرفض برسالة، لا بخطأ خادم */
    public function test_username_taken_by_a_soft_deleted_user_is_refused_cleanly(): void
    {
        $admin = $this->admin();
        $other = User::where('id', '!=', $admin->id)->firstOrFail();
        $takenUsername = $other->username;
        $other->delete();

        $this->actingAs($admin)->post('/settings/account', [
            'current_password' => 'secret-current',
            'username'         => $takenUsername,
        ])->assertSessionHasErrors('username');

        $this->assertNotSame($takenUsername, $admin->fresh()->username);
    }

    public function test_password_change_ends_sessions_on_other_devices(): void
    {
        $admin = $this->admin();

        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id'            => 'other-device-session',
            'user_id'       => $admin->id,
            'ip_address'    => '10.0.0.9',
            'user_agent'    => 'other device',
            'payload'       => base64_encode('x'),
            'last_activity' => time(),
        ]);

        $this->actingAs($admin)->post('/settings/account', [
            'current_password'      => 'secret-current',
            'username'              => $admin->username,
            'password'              => 'rotated-password',
            'password_confirmation' => 'rotated-password',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'other-device-session']);
    }

    /**
     * الحماية من قفل الحساب على صاحبه: تسجيل الدخول في هذا النظام يُرفض إن وُجدت
     * جلسة نشطة أخرى، فلو بقي صف الجلسة القديم بعد تغيير كلمة المرور لمُنع
     * المدير من الدخول ببياناته الجديدة.
     */
    public function test_admin_can_log_in_again_after_changing_password(): void
    {
        config(['session.driver' => 'database']);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/settings/account', [
            'current_password'      => 'secret-current',
            'username'              => 'rotated-admin',
            'password'              => 'rotated-password',
            'password_confirmation' => 'rotated-password',
        ])->assertSessionHasNoErrors();

        // محاكاة إغلاق المتصفح: لا جلسة مفتوحة، ثم دخول ببيانات جديدة
        auth()->logout();
        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $admin->id)->delete();

        $this->post('/login', ['username' => 'rotated-admin', 'password' => 'rotated-password'])
            ->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($admin->fresh());
    }

    public function test_staff_without_settings_permission_cannot_reach_it(): void
    {
        $employee = User::role('receptionist')->firstOrFail();

        $response = $this->actingAs($employee)->post('/settings/account', [
            'current_password' => 'whatever',
            'username'         => 'sneaky',
        ]);

        $response->assertRedirect();
        $this->assertNotNull(session('error'));
        $this->assertNotSame('sneaky', $employee->fresh()->username);
    }
}
