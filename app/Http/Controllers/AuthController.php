<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditLogService;
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
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = \App\Models\User::where('username', $credentials['username'])
            ->where('is_active', true)
            ->first();

        if (!$user || !Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()->withErrors(['username' => 'بيانات الدخول غير صحيحة'])->withInput();
        }

        if (!$user->is_active) {
            Auth::logout();
            return back()->withErrors(['username' => 'حسابك غير مفعل. تواصل مع المدير']);
        }

        $request->session()->regenerate();

        AuditLogService::log('login', null, null, ['username' => $credentials['username']], $user);

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
