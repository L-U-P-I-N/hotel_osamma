<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordResetController extends Controller
{
    public function showRequest()
    {
        return view('auth.forgot-password');
    }

    public function resetWithBackupCode(Request $request)
    {
        $request->validate([
            'username'    => 'required|string',
            'backup_code' => 'required|string',
            'password'    => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !$user->backup_code || !Hash::check($request->backup_code, $user->backup_code)) {
            return back()->withErrors(['backup_code' => 'اسم المستخدم أو رمز الاسترداد غير صحيح'])->withInput(['username' => $request->username]);
        }

        $plainCode = $this->generateBackupCode();

        $user->update([
            'password'    => Hash::make($request->password),
            'backup_code' => Hash::make($plainCode),
        ]);

        return redirect()->route('login')
            ->with('success', 'تم تغيير كلمة المرور بنجاح. رمز الاسترداد الجديد: ' . $plainCode . ' — احتفظ به في مكان آمن');
    }

    public function resetPassword(Request $request)
    {
        return $this->resetWithBackupCode($request);
    }

    private function generateBackupCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $segments = [];
        for ($i = 0; $i < 3; $i++) {
            $segment = '';
            for ($j = 0; $j < 4; $j++) {
                $segment .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $segments[] = $segment;
        }
        return implode('-', $segments);
    }
}
