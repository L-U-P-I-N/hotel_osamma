<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const HOTEL_LOGO = 'hotel_logo';

    // نطاق سعر الجناح كاملاً (غرفتان) — على مستوى الفندق لا على نوع الغرفة،
    // لأن أقسام الأجنحة تُصنَّف غرفاً عادية (القسم غرفة أصلاً)، فلو رُبط النطاق
    // بالنوع لما طُبِّق على أي جناح.
    public const SUITE_MIN_PRICE = 'suite_min_price';
    public const SUITE_MAX_PRICE = 'suite_max_price';

    /**
     * بيانات الفندق التي تظهر في رأس كل فاتورة وتقرير — مفتاح => [التسمية،
     * تلميح]. تُدار كلها من شاشة الإعدادات، ويُقرأ منها رأس التصدير الموحّد.
     * حقول _en تظهر في الجهة اليسرى من الرأس مقابل العربية على اليمين.
     */
    public const PROFILE_FIELDS = [
        'hotel_name_ar'    => ['اسم الفندق (عربي)',        'يظهر في رأس كل تقرير وفاتورة'],
        'hotel_name_en'    => ['اسم الفندق (إنجليزي)',      'Hotel name in English'],
        'hotel_tagline_ar' => ['الوصف/التصنيف (عربي)',      'مثال: فندق سياحي — ثلاث نجوم'],
        'hotel_tagline_en' => ['الوصف/التصنيف (إنجليزي)',   'Example: Tourist Hotel'],
        'hotel_address_ar' => ['العنوان (عربي)',            'الشارع/الحي والمدينة'],
        'hotel_address_en' => ['العنوان (إنجليزي)',         'Street / District, City'],
        'hotel_phone'      => ['رقم التواصل الأساسي',       'يظهر في كل التقارير'],
        'hotel_phone2'     => ['رقم تواصل إضافي',           'اختياري'],
        'hotel_whatsapp'   => ['واتساب',                    'اختياري'],
        'hotel_email'      => ['البريد الإلكتروني',          'اختياري'],
        'hotel_website'    => ['الموقع الإلكتروني',          'اختياري'],
        'hotel_license_no' => ['رقم الترخيص السياحي',        'يظهر في التقارير الرسمية'],
        'hotel_cr_no'      => ['السجل التجاري',             'اختياري'],
        'hotel_tax_no'     => ['الرقم الضريبي',             'اختياري'],
    ];

    /**
     * قيمة إعداد. تُخزَّن بالكاش لأن الشعار يُقرأ في كل صفحة وكل تصدير PDF —
     * فلا نريد استعلاماً لكل قراءة. أي فشل (كجدول غير مُرحَّل بعد على نسخة
     * قديمة) يعيد القيمة الافتراضية بدل إسقاط الصفحة.
     */
    public static function get(string $key, $default = null)
    {
        try {
            return Cache::rememberForever("setting.{$key}", function () use ($key) {
                return static::where('key', $key)->value('value');
            }) ?? $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    public static function forget(string $key): void
    {
        static::where('key', $key)->delete();
        Cache::forget("setting.{$key}");
    }

    /**
     * شعار الفندق كـdata URI جاهز للاستخدام في <img src> بالواجهة وبـdompdf،
     * أو null إن لم يُرفع شعار. يسقط تلقائياً للملف القديم في public/images
     * (توافق مع النسخ التي رُفع شعارها قبل وجود هذه الشاشة).
     */
    /** [min, max] لسعر الجناح كاملاً، أو null إن لم يضبطه المدير بعد */
    public static function suitePriceRange(): ?array
    {
        $min = (float) self::get(self::SUITE_MIN_PRICE, 0);
        $max = (float) self::get(self::SUITE_MAX_PRICE, 0);

        if ($min <= 0 || $max < $min) {
            return null;
        }

        return [round($min, 2), round($max, 2)];
    }

    public static function hotelLogo(): ?string
    {
        $logo = static::get(self::HOTEL_LOGO);
        if ($logo) {
            return $logo;
        }

        $legacy = public_path('images/hotel-logo.png');
        return file_exists($legacy) ? $legacy : null;
    }

    /**
     * بيانات الفندق كمصفوفة جاهزة للرأس الموحّد. تسقط تلقائياً لبيانات جدول
     * hotels القديم حين لا تُضبط من الإعدادات، فلا يفرغ الرأس على نسخة لم
     * تُملأ إعداداتها بعد.
     */
    public static function hotelProfile(): array
    {
        $profile = [];
        foreach (array_keys(self::PROFILE_FIELDS) as $key) {
            $profile[$key] = static::get($key);
        }

        try {
            $hotel = Hotel::first();
        } catch (\Throwable $e) {
            $hotel = null;
        }

        $profile['hotel_name_ar']    = $profile['hotel_name_ar']    ?: ($hotel?->name ?? null);
        $profile['hotel_address_ar'] = $profile['hotel_address_ar'] ?: ($hotel?->address ?? null);
        $profile['hotel_phone']      = $profile['hotel_phone']      ?: ($hotel?->phone ?? null);

        return $profile;
    }

    /** أرقام التواصل المضبوطة فقط، مجمّعة في سطر واحد للتذييل/الرأس. */
    public static function contactLine(): string
    {
        $p = static::hotelProfile();

        $parts = array_filter([
            $p['hotel_phone']    ? 'هاتف: ' . $p['hotel_phone'] : null,
            $p['hotel_phone2']   ? $p['hotel_phone2'] : null,
            $p['hotel_whatsapp'] ? 'واتساب: ' . $p['hotel_whatsapp'] : null,
            $p['hotel_email']    ?: null,
            $p['hotel_website']  ?: null,
        ]);

        return implode('  •  ', $parts);
    }
}
