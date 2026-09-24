<?php
namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * تقوية ضد الهجمات الآلية: تخمين كلمات المرور يُوقَف بعد عدد محدَّد من
 * المحاولات، وكل استجابة تحمل ترويسات تمنع التأطير وتخمين نوع الملفات.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login:admin|127.0.0.1');
    }

    private function attemptLogin(string $password = 'كلمة-خاطئة'): \Illuminate\Testing\TestResponse
    {
        return $this->post(route('login.post'), [
            'username' => 'admin',
            'password' => $password,
        ]);
    }

    /** رسالة الخطأ الظاهرة للمستخدم بعد آخر محاولة. */
    private function lastLoginError(): string
    {
        return session('errors')?->get('username')[0] ?? '';
    }

    public function test_repeated_wrong_passwords_are_blocked_after_five_attempts(): void
    {
        // خمس محاولات فاشلة: كلٌّ منها يردّ "بيانات الدخول غير صحيحة" فقط
        foreach (range(1, 5) as $ignored) {
            $this->attemptLogin();
            $this->assertSame('بيانات الدخول غير صحيحة', $this->lastLoginError());
            $this->assertGuest();
        }

        // السادسة تُحظر مؤقتاً برسالة تُبيّن المدة المتبقية
        $this->attemptLogin();

        $this->assertStringContainsString('تم إيقاف المحاولات مؤقتاً', $this->lastLoginError());
        $this->assertGuest();
    }

    public function test_the_lockout_blocks_even_the_correct_password(): void
    {
        foreach (range(1, 5) as $ignored) {
            $this->attemptLogin();
        }

        // كلمة المرور الصحيحة لا تنفع أثناء الحظر — وإلا لم يوقف الحظر تخميناً
        $this->attemptLogin('Admin@1234');

        $this->assertGuest();
        $this->assertStringContainsString('تم إيقاف المحاولات مؤقتاً', $this->lastLoginError());
    }

    public function test_a_successful_login_clears_the_counter(): void
    {
        $this->attemptLogin();
        $this->attemptLogin();
        $this->flushSession();

        $this->post(route('login.post'), ['username' => 'admin', 'password' => 'Admin@1234']);
        $this->assertAuthenticated();

        $this->assertSame(0, RateLimiter::attempts('login:admin|127.0.0.1'));
    }

    public function test_every_response_carries_the_protective_headers(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'same-origin');
        $this->assertStringContainsString("frame-ancestors 'none'", $response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('X-Powered-By'));
    }

    public function test_authenticated_pages_carry_them_too(): void
    {
        $this->actingAs(User::role('admin')->firstOrFail())
            ->get(route('employees.index'))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY');
    }

    /** بيانات الدخول الخاطئة لا تكشف إن كان اسم المستخدم موجوداً أصلاً. */
    public function test_the_error_message_does_not_reveal_whether_the_user_exists(): void
    {
        $this->post(route('login.post'), ['username' => 'admin', 'password' => 'خطأ']);
        $existing = session('errors')->get('username')[0];
        $this->flushSession();

        $this->post(route('login.post'), ['username' => 'لا-يوجد-اطلاقاً', 'password' => 'خطأ']);
        $missing = session('errors')->get('username')[0];

        $this->assertSame($existing, $missing);
    }
}
