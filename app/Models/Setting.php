<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public const HOTEL_LOGO = 'hotel_logo';

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
    public static function hotelLogo(): ?string
    {
        $logo = static::get(self::HOTEL_LOGO);
        if ($logo) {
            return $logo;
        }

        $legacy = public_path('images/hotel-logo.png');
        return file_exists($legacy) ? $legacy : null;
    }
}
