<?php
namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\ShiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    /** حد محاولات الدخول الفاشلة قبل الحظر المؤقت. */
    private const MAX_LOGIN_ATTEMPTS = 5;

    /** مدة الحظر بالثواني بعد استنفاد المحاولات. */
    private const LOCKOUT_SECONDS = 300;

    /**
     * مفتاح المحاولات: اسم المستخدم + عنوان الشبكة معاً. الربط بالاثنين يمنع
     * سكربت تخمين كلمات المرور على حساب واحد، ولا يسمح لمهاجم من عنوان واحد
     * بحظر كل الموظفين بتجريب أسمائهم.
     */
    private function throttleKey(Request $request): string
    {
        return 'login:' . mb_strtolower((string) $request->input('username')) . '|' . $request->ip();
    }

    /**
     * يمنع المحاولة إن استُنفد الحد، ويُرجع رسالة بالمدة المتبقية. الحظر
     * مؤقت بذاته فلا يحتاج تدخّل المدير لفكّه.
     */
    private function ensureNotLockedOut(Request $request): ?\Illuminate\Http\RedirectResponse
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_LOGIN_ATTEMPTS)) {
            return null;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));
        $minutes = (int) ceil($seconds / 60);

        // عمود action في سجل التدقيق قائمة قيم محدَّدة؛ نسجّلها كمحاولة دخول
        // موسومة بأنها محظورة بدل إضافة قيمة جديدة للعمود.
        AuditLogService::log('login', null, null, [
            'username' => $request->input('username'),
            'blocked'  => true,
            'reason'   => 'تجاوز حد محاولات الدخول الفاشلة',
        ]);

        return back()->withErrors([
            'username' => 'تم إيقاف المحاولات مؤقتاً بعد ' . self::MAX_LOGIN_ATTEMPTS
                . ' محاولات فاشلة. أعد المحاولة بعد '
                . ($seconds < 60 ? $seconds . ' ثانية' : $minutes . ' دقيقة')
                . ' أو راجع المدير لإعادة تعيين كلمة المرور.',
        ])->withInput($request->except('password'));
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($locked = $this->ensureNotLockedOut($request)) {
            return $locked;
        }

        $user = \App\Models\User::where('username', $request->username)
            ->where('is_active', true)
            ->first();

        if (!$user || !Auth::attempt(['username' => $request->username, 'password' => $request->password], false)) {
            // تُحتسب المحاولة الفاشلة وحدها؛ الدخول الناجح يمسح العدّاد
            RateLimiter::hit($this->throttleKey($request), self::LOCKOUT_SECONDS);

            return back()->withErrors(['username' => 'بيانات الدخول غير صحيحة'])->withInput();
        }

        RateLimiter::clear($this->throttleKey($request));

        // تحقق من وجود جلسة نشطة لهذا المستخدم على جهاز آخر
        $lifetimeSeconds = config('session.lifetime', 120) * 60;
        $cutoff = time() - $lifetimeSeconds;

        $activeSessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('last_activity', '>=', $cutoff)
            ->count();

        if ($activeSessions > 0) {
            // تسجيل الخروج مباشرة لأن Auth::attempt سجّل الدخول
            Auth::logout();
            return back()
                ->withErrors(['username' => 'هذا الحساب مستخدم حالياً على جهاز آخر. يرجى تسجيل الخروج منه أولاً.'])
                ->withInput()
                ->with('show_force_login', true)
                ->with('force_username', $request->username);
        }

        $request->session()->regenerate();

        AuditLogService::log('login', null, null, ['username' => $request->username], $user);

        return $this->afterLoginRedirect($user);
    }

    /**
     * لا تُفتح الوردية تلقائياً عند الدخول — كان ذلك يُنشئ وردية لمن سجّل دخوله
     * للتصفّح فقط، ويثبّت تاريخها على يوم الدخول لا يوم العمل الفعلي. بدلاً منه
     * نتحقق: إن لم تكن له وردية مفتوحة نوجّهه لصفحة الوردية ليفتحها بنفسه،
     * وإلا فاللوحة الرئيسية كالمعتاد.
     */
    private function afterLoginRedirect(\App\Models\User $user)
    {
        if ($user->can('shifts.view') && ! app(ShiftService::class)->getActiveShift($user)) {
            return redirect()->route('shifts.index')
                ->with('warning', 'لا توجد لك وردية مفتوحة — افتح وردية قبل تسجيل أي مستلمة أو سحب.');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function forceLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($locked = $this->ensureNotLockedOut($request)) {
            return $locked;
        }

        $user = \App\Models\User::where('username', $request->username)
            ->where('is_active', true)
            ->first();

        if (!$user || !Auth::attempt(['username' => $request->username, 'password' => $request->password], false)) {
            RateLimiter::hit($this->throttleKey($request), self::LOCKOUT_SECONDS);

            return back()->withErrors(['username' => 'بيانات الدخول غير صحيحة'])->withInput();
        }

        RateLimiter::clear($this->throttleKey($request));

        // حذف جميع الجلسات القديمة لهذا المستخدم في قاعدة البيانات
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $request->session()->regenerate();

        AuditLogService::log('login', null, null, ['username' => $request->username, 'force_override' => true], $user);

        return $this->afterLoginRedirect($user);
    }

    public function logout(Request $request)
    {
        AuditLogService::log('logout', null, null, null, auth()->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
