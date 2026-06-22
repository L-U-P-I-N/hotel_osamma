<?php
namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\ShiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
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

        $user = \App\Models\User::where('username', $request->username)
            ->where('is_active', true)
            ->first();

        if (!$user || !Auth::attempt(['username' => $request->username, 'password' => $request->password], $request->boolean('remember'))) {
            return back()->withErrors(['username' => 'بيانات الدخول غير صحيحة'])->withInput();
        }

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

        // فتح وردية تلقائياً عند تسجيل الدخول
        app(ShiftService::class)->openShift($user);

        return redirect()->intended(route('dashboard'));
    }

    public function forceLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = \App\Models\User::where('username', $request->username)
            ->where('is_active', true)
            ->first();

        if (!$user || !Auth::attempt(['username' => $request->username, 'password' => $request->password], false)) {
            return back()->withErrors(['username' => 'بيانات الدخول غير صحيحة'])->withInput();
        }

        // حذف جميع الجلسات القديمة لهذا المستخدم في قاعدة البيانات
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $request->session()->regenerate();

        AuditLogService::log('login', null, null, ['username' => $request->username, 'force_override' => true], $user);
        app(ShiftService::class)->openShift($user);

        return redirect()->intended(route('dashboard'));
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
