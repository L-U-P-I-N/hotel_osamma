<?php
namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'hotelLogo' => Setting::hotelLogo(),
            'profile'   => Setting::hotelProfile(),
            'fields'    => Setting::PROFILE_FIELDS,
            'account'   => auth()->user(),
        ]);
    }

    /** حفظ بيانات الفندق التي تظهر في رأس كل فاتورة وتقرير. */
    public function updateProfile(Request $request)
    {
        $keys = array_keys(Setting::PROFILE_FIELDS);

        $rules = [];
        foreach ($keys as $key) {
            $rules[$key] = 'nullable|string|max:255';
        }
        $rules['hotel_email'] = 'nullable|email|max:255';

        $validated = $request->validate($rules, [
            'hotel_email.email' => 'البريد الإلكتروني غير صالح',
        ]);

        foreach ($keys as $key) {
            $value = trim((string) ($validated[$key] ?? ''));
            $value === '' ? Setting::forget($key) : Setting::set($key, $value);
        }

        AuditLogService::log('update', null, null, ['action' => 'hotel_profile_updated'], auth()->user());

        return back()->with('success', 'تم حفظ بيانات الفندق — ستظهر في رأس كل فاتورة وتقرير');
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,webp|max:2048',
        ], [
            'logo.required' => 'اختر ملف الشعار أولاً',
            'logo.image'    => 'الملف يجب أن يكون صورة',
            'logo.mimes'    => 'صيغة الصورة يجب أن تكون PNG أو JPG أو WEBP',
            'logo.max'      => 'حجم الصورة لا يتجاوز 2 ميجابايت',
        ]);

        $file = $request->file('logo');

        // يُخزَّن كـdata URI بقاعدة البيانات (لا كملف في public/) ليصمد أمام
        // إعادة النشر على الاستضافة، ويعمل مباشرةً في الواجهة وفي dompdf معاً.
        $dataUri = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getRealPath()));

        Setting::set(Setting::HOTEL_LOGO, $dataUri);
        AuditLogService::log('update', null, null, ['setting' => Setting::HOTEL_LOGO, 'action' => 'hotel_logo_updated'], auth()->user());

        return back()->with('success', 'تم تحديث شعار الفندق — سيظهر في النظام وكل تصديرات PDF');
    }

    /**
     * تغيير اسم المستخدم و/أو كلمة المرور لحساب الداخل نفسه.
     * لا يمسّ أي حساب آخر — تعديل حسابات الموظفين مكانه صفحة المستخدمين.
     */
    public function updateAccount(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'username'         => [
                'required', 'string', 'min:3', 'max:50', 'alpha_dash',
                // بلا استثناء للمحذوفين: فهرس قاعدة البيانات على username فريد
                // ولا يعرف الحذف الناعم، فلو سمحنا هنا لفشل الحفظ بخطأ 500.
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'password'         => 'nullable|string|min:8|confirmed',
        ], [
            'current_password.required' => 'أدخل كلمة المرور الحالية للتأكيد',
            'username.required'         => 'اسم المستخدم مطلوب',
            'username.min'              => 'اسم المستخدم لا يقل عن 3 أحرف',
            'username.max'              => 'اسم المستخدم لا يتجاوز 50 حرفاً',
            'username.alpha_dash'       => 'اسم المستخدم يقبل الحروف والأرقام والشرطات فقط (بدون مسافات أو رموز)',
            'username.unique'           => 'اسم المستخدم محجوز لمستخدم آخر — اختر غيره',
            'password.min'              => 'كلمة المرور الجديدة لا تقل عن 8 أحرف',
            'password.confirmed'        => 'تأكيد كلمة المرور لا يطابق كلمة المرور الجديدة',
        ]);

        // كلمة المرور الحالية شرط لأي تغيير — تمنع من يجد الجهاز مفتوحاً
        // من الاستيلاء على الحساب بتبديل بياناته.
        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withInput()->withErrors([
                'current_password' => 'كلمة المرور الحالية غير صحيحة',
            ]);
        }

        $newUsername   = trim($validated['username']);
        $usernameChanged = $newUsername !== $user->username;
        $passwordChanged = !empty($validated['password']);

        if (!$usernameChanged && !$passwordChanged) {
            return back()->with('success', 'لم يتغيّر شيء — اسم المستخدم كما هو ولم تُدخل كلمة مرور جديدة');
        }

        $oldUsername = $user->username;

        $user->username = $newUsername;
        if ($passwordChanged) {
            $user->password = $validated['password']; // يُجزَّأ تلقائياً عبر cast: hashed
        }
        $user->save();

        if ($passwordChanged) {
            // كلمة المرور القديمة لم تعد صالحة، فلا يصح أن تبقى جلسة مفتوحة بها.
            // تُحذف كل صفوف جلسات هذا الحساب ثم تُجدَّد الجلسة الحالية بـ destroy،
            // فيتبقّى صف واحد فقط للجلسة الجديدة. لو تُرك صف قديم لاعتبره فحص
            // "الحساب مستخدم على جهاز آخر" جلسةً نشطة ومنع صاحبه من الدخول لاحقاً.
            // فحص الجدول لا سائق الجلسات: أي إعداد لا يخزّن الجلسات في القاعدة
            // لن يكون لديه هذا الجدول، فنتجاوز التنظيف بدل أن نفشل.
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            $request->session()->regenerate(true);
        }

        AuditLogService::log('update', $user, ['username' => $oldUsername], [
            'action'           => 'account_credentials_updated',
            'username'         => $newUsername,
            'password_changed' => $passwordChanged, // لا تُسجَّل كلمة المرور نفسها إطلاقاً
        ], $user);

        $parts = [];
        if ($usernameChanged) $parts[] = 'اسم المستخدم';
        if ($passwordChanged) $parts[] = 'كلمة المرور';

        return back()->with('success', 'تم تحديث ' . implode(' و', $parts)
            . ($passwordChanged ? ' — استخدم البيانات الجديدة في الدخول القادم، وأُنهيت جلساتك على الأجهزة الأخرى' : ''));
    }

    public function removeLogo()
    {
        Setting::forget(Setting::HOTEL_LOGO);
        AuditLogService::log('update', null, null, ['setting' => Setting::HOTEL_LOGO, 'action' => 'hotel_logo_removed'], auth()->user());

        return back()->with('success', 'تم حذف شعار الفندق');
    }
}
