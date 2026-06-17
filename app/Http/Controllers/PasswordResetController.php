<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    // Step 1 — show form to enter username
    public function showRequest()
    {
        return view('auth.forgot-password');
    }

    // Step 2 — send OTP via WhatsApp
    public function sendOtp(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
        ], [
            'username.required' => 'اسم المستخدم مطلوب',
        ]);

        $user = User::where('username', $request->username)->where('is_active', true)->first();

        // Always show success message to prevent user enumeration
        if (!$user || !$user->phone) {
            return back()->with('otp_sent', true)->with('username', $request->username);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put('password_reset_otp_' . $user->id, $otp, now()->addMinutes(10));
        Cache::put('password_reset_user_' . $request->username, $user->id, now()->addMinutes(10));

        app(WhatsAppService::class)->sendOtp($user->phone, $otp);

        return back()->with('otp_sent', true)->with('username', $request->username);
    }

    // Step 3 — show OTP + new password form
    public function showVerify(Request $request)
    {
        $username = $request->query('username', session('username'));
        return view('auth.verify-otp', compact('username'));
    }

    // Step 4 — verify OTP and reset password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'username'              => 'required|string',
            'otp'                   => 'required|digits:6',
            'password'              => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
        ], [
            'otp.required'              => 'رمز التحقق مطلوب',
            'otp.digits'                => 'رمز التحقق يجب أن يكون 6 أرقام',
            'password.required'         => 'كلمة المرور مطلوبة',
            'password.min'              => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'password.confirmed'        => 'كلمة المرور وتأكيدها غير متطابقين',
            'password_confirmation.required' => 'تأكيد كلمة المرور مطلوب',
        ]);

        $userId = Cache::get('password_reset_user_' . $request->username);

        if (!$userId) {
            return back()->withErrors(['otp' => 'انتهت صلاحية الرمز، اطلب رمزاً جديداً'])->withInput();
        }

        $cachedOtp = Cache::get('password_reset_otp_' . $userId);

        if (!$cachedOtp || $cachedOtp !== $request->otp) {
            return back()->withErrors(['otp' => 'رمز التحقق غير صحيح'])->withInput();
        }

        $user = User::findOrFail($userId);
        $user->update(['password' => Hash::make($request->password)]);

        Cache::forget('password_reset_otp_' . $userId);
        Cache::forget('password_reset_user_' . $request->username);

        return redirect()->route('login')->with('success', 'تم تغيير كلمة المرور بنجاح، يمكنك الدخول الآن');
    }
}
