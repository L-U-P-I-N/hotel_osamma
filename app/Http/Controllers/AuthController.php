<?php
namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\ShiftService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $request->session()->regenerate();

        AuditLogService::log('login', null, null, ['username' => $request->username], $user);

        // فتح وردية تلقائياً عند تسجيل الدخول
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
