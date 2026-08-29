<?php
namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return view('settings.index', [
            'hotelLogo' => Setting::hotelLogo(),
            'profile'   => Setting::hotelProfile(),
            'fields'    => Setting::PROFILE_FIELDS,
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

    public function removeLogo()
    {
        Setting::forget(Setting::HOTEL_LOGO);
        AuditLogService::log('update', null, null, ['setting' => Setting::HOTEL_LOGO, 'action' => 'hotel_logo_removed'], auth()->user());

        return back()->with('success', 'تم حذف شعار الفندق');
    }
}
