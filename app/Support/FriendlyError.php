<?php
namespace App\Support;

use Illuminate\Database\QueryException;

/**
 * ترجمة أخطاء النظام الخام إلى سبب مفهوم بالعربية.
 *
 * مستخدم النظام موظف استقبال لا مبرمج؛ نصّ SQL الإنجليزي أو رمز الخطأ
 * لا يقول له ما الذي يفعله. هنا نحوّل أشهر أسباب الرفض إلى جملة تصف
 * المشكلة والإجراء المطلوب.
 */
class FriendlyError
{
    /** أسماء الجداول بالعربية لتوضيح أي بيانات يتعلّق بها الرفض. */
    private const TABLES = [
        'reservations'  => 'الحجوزات',
        'guests'        => 'النزلاء',
        'rooms'         => 'الغرف',
        'payments'      => 'المستلمات',
        'withdrawals'   => 'السحبيات',
        'expenses'      => 'المصروفات',
        'employees'     => 'الموظفين',
        'salaries'      => 'الرواتب',
        'shifts'        => 'الورديات',
        'users'         => 'المستخدمين',
        'companions'    => 'المرافقين',
        'extra_charges' => 'الرسوم الإضافية',
        'room_types'    => 'أنواع الغرف',
    ];

    public static function forDatabase(QueryException $e): string
    {
        $raw  = $e->getMessage();
        $code = (string) ($e->errorInfo[1] ?? $e->getCode());

        // تكرار قيمة يجب أن تكون فريدة (رقم غرفة، اسم مستخدم، رقم هوية…)
        if (self::matches($raw, ['Duplicate entry', 'UNIQUE constraint failed', '1062'])) {
            $field = self::arabicField(self::extractColumn($raw));

            return $field
                ? "القيمة المدخلة في «{$field}» مسجّلة من قبل. اختر قيمة أخرى."
                : 'هذه البيانات مسجّلة من قبل ولا يمكن تكرارها. راجع المدخلات واختر قيمة أخرى.';
        }

        // حذف سجل مرتبط بسجلات أخرى
        if (self::matches($raw, ['foreign key constraint fails', 'FOREIGN KEY constraint failed', '1451'])) {
            $table = self::arabicTable($raw);

            return $table
                ? "لا يمكن الحذف لأن هذا السجل مرتبط ببيانات في {$table}. احذف المرتبط أولاً أو عطّل السجل بدل حذفه."
                : 'لا يمكن الحذف لأن هذا السجل مرتبط ببيانات أخرى في النظام. احذف المرتبط أولاً أو عطّل السجل بدل حذفه.';
        }

        // ربط بسجل غير موجود
        if (self::matches($raw, ['1452', 'Cannot add or update a child row'])) {
            return 'العنصر المختار غير موجود في النظام — قد يكون حُذف. حدّث الصفحة وأعد الاختيار.';
        }

        // حقل إلزامي وصل فارغاً
        if (self::matches($raw, ['cannot be null', 'NOT NULL constraint failed', '1048'])) {
            $field = self::arabicField(self::extractColumn($raw));

            return $field
                ? "حقل «{$field}» مطلوب ووصل فارغاً. أكمل تعبئته ثم أعد الحفظ."
                : 'أحد الحقول المطلوبة وصل فارغاً. راجع النموذج وأكمل الحقول الناقصة.';
        }

        // قيمة أطول أو أكبر من حدّ العمود
        if (self::matches($raw, ['Data too long', '1406'])) {
            $field = self::arabicField(self::extractColumn($raw));

            return $field
                ? "النص المدخل في «{$field}» أطول من المسموح. اختصره ثم أعد الحفظ."
                : 'أحد الحقول يحتوي نصاً أطول من المسموح. اختصره ثم أعد الحفظ.';
        }

        if (self::matches($raw, ['Out of range', '1264'])) {
            return 'الرقم المدخل أكبر من الحد المسموح به. راجع المبلغ أو العدد المدخل.';
        }

        // تعذّر الوصول لقاعدة البيانات
        if (self::matches($raw, ['2002', 'Connection refused', 'server has gone away', '1045', 'could not find driver'])) {
            return 'تعذّر الاتصال بقاعدة البيانات. تأكّد من اتصال الخادم ثم أعد المحاولة، وإن تكرر الأمر راجع الدعم الفني.';
        }

        if (self::matches($raw, ['Lock wait timeout', 'Deadlock'])) {
            return 'العملية تأخّرت لانشغال النظام بعملية أخرى على نفس البيانات. أعد المحاولة بعد لحظات.';
        }

        // سبب غير معروف: رسالة عامة واضحة دون كشف تفاصيل تقنية
        return 'تعذّر حفظ البيانات بسبب خطأ في قاعدة البيانات. راجع المدخلات وأعد المحاولة، وإن تكرر الأمر راجع الدعم الفني.';
    }

    /** صياغة مدة بالثواني بالعربية: "٣٠ ثانية" أو "دقيقتين". */
    public static function duration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . ' ثانية';
        }

        $minutes = (int) ceil($seconds / 60);

        return match (true) {
            $minutes === 1 => 'دقيقة',
            $minutes === 2 => 'دقيقتين',
            $minutes <= 10 => $minutes . ' دقائق',
            default        => $minutes . ' دقيقة',
        };
    }

    private static function matches(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /** اسم العمود المذكور في نصّ الخطأ، إن أمكن استخراجه. */
    private static function extractColumn(string $raw): ?string
    {
        // MySQL: Column 'x' ... / key 'table.column' — SQLite: table.column
        foreach (["/Column '([^']+)'/", "/column '?([A-Za-z0-9_.]+)'?/i", "/key '[^']*?\.?([A-Za-z0-9_]+)'/"] as $pattern) {
            if (preg_match($pattern, $raw, $m)) {
                $parts = explode('.', $m[1]);

                return end($parts) ?: null;
            }
        }

        return null;
    }

    /** ترجمة اسم العمود بالاعتماد على أسماء الحقول المترجمة أصلاً للتحقق. */
    private static function arabicField(?string $column): ?string
    {
        if (!$column) {
            return null;
        }

        $label = trans('validation.attributes.' . $column);

        return $label === 'validation.attributes.' . $column ? null : $label;
    }

    private static function arabicTable(string $raw): ?string
    {
        foreach (self::TABLES as $table => $label) {
            if (str_contains($raw, "`{$table}`") || str_contains($raw, "\"{$table}\"") || str_contains($raw, " {$table} ")) {
                return $label;
            }
        }

        return null;
    }
}
