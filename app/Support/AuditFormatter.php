<?php
namespace App\Support;

use App\Models\Employee;
use App\Models\Guest;
use App\Models\Room;
use App\Models\User;

/**
 * يحوّل قيم سجل المراجعة الخام إلى نص عربي مفهوم للمدير/المالك: أسماء بدل
 * المعرّفات الرقمية، تسميات عربية بدل قيم enum الإنجليزية، ومبالغ مُنسَّقة.
 *
 * أسماء الكيانات المُستخرَجة تُحفَظ بذاكرة الطلب لتفادي استعلام لكل حقل في
 * كل صف من صفحة السجل.
 */
class AuditFormatter
{
    private static array $cache = [];

    /** حقول لا تهمّ المراجعة (بيانات نظام أو علاقات كاملة). */
    public const NOISE_FIELDS = [
        'id', 'created_at', 'updated_at', 'deleted_at',
        'room', 'linked_room', 'guest', 'payments', 'password', 'remember_token',
        'id_number_hash', 'recompute_dismissed',
    ];

    private const FIELD_LABELS = [
        'guest_id' => 'النزيل', 'room_id' => 'الغرفة', 'linked_room_id' => 'الغرفة المرتبطة',
        'reservation_id' => 'الحجز', 'employee_id' => 'الموظف', 'shift_id' => 'الوردية',
        'user_id' => 'المستخدم', 'account_id' => 'الحساب',
        'created_by' => 'أنشأه', 'cancelled_by' => 'ألغاه', 'settled_by' => 'سوّاه',
        'processed_by' => 'نفّذه', 'received_by' => 'استلمها', 'paid_by' => 'صرفها',
        'checked_out_by' => 'سجّل خروجه', 'inspected_by' => 'فحصها', 'added_by' => 'أضافه',
        'status' => 'الحالة', 'payment_status' => 'حالة الدفع',
        'check_in_date' => 'تاريخ الدخول', 'check_in_time' => 'وقت الدخول',
        'check_out_date' => 'تاريخ الخروج', 'check_out_time' => 'وقت الخروج',
        'actual_check_out' => 'وقت الخروج الفعلي',
        'total_amount' => 'الإجمالي', 'paid_amount' => 'المدفوع', 'amount' => 'المبلغ',
        'currency' => 'العملة', 'discount_type' => 'نوع الخصم', 'discount_value' => 'قيمة الخصم',
        'discount_amount' => 'مبلغ الخصم', 'discount_reason' => 'سبب الخصم',
        'cancellation_reason' => 'سبب الإلغاء', 'cancelled_at' => 'وقت الإلغاء',
        'notes' => 'ملاحظات', 'origin' => 'جهة القدوم', 'purpose' => 'الغرض',
        'category' => 'التصنيف', 'recipient_name' => 'المستلم', 'description' => 'الوصف',
        'expense_date' => 'تاريخ المصروف', 'settled_at' => 'وقت التسوية',
        'payment_method' => 'طريقة الدفع', 'method' => 'الطريقة', 'reason' => 'السبب',
        'base_salary' => 'الراتب الأساسي', 'bonuses' => 'الحوافز', 'deductions' => 'الخصومات',
        'withdrawals_deduction' => 'خصم السلف', 'attendance_deduction' => 'خصم الغياب',
        'net_salary' => 'صافي الراتب', 'name' => 'الاسم', 'email' => 'البريد',
        'phone' => 'الجوال', 'is_active' => 'نشط', 'room_number' => 'رقم الغرفة',
        'floor' => 'الطابق', 'action' => 'الإجراء',
        'withdrawal_type' => 'نوع السحب', 'funding_source' => 'مصدر التمويل',
        'withdrawn_by_name' => 'المستلم', 'handed_by_name' => 'سلّمها',
        'price_per_night' => 'سعر الليلة', 'first_night_price' => 'سعر الليلة الأولى',
        'renewal_price_per_night' => 'سعر التجديد', 'nights' => 'عدد الليالي',
        'shortfall' => 'العجز/الزيادة', 'is_closed' => 'مقفلة',
        'total_received_yer' => 'المستلم (ر.ي)', 'total_withdrawals_yer' => 'السحبيات (ر.ي)',
        'compensation_amount' => 'مبلغ التعويض', 'damage_description' => 'وصف الأضرار',
        'has_damage' => 'يوجد أضرار', 'compensation_status' => 'حالة التعويض',
        'segment_type' => 'نوع الفترة', 'segment_id' => 'رقم الفترة',
        'from_date' => 'اعتباراً من', 'delta' => 'فرق المبلغ',
        'nationality' => 'الجنسية', 'occupation' => 'المهنة', 'id_type' => 'نوع الهوية',
        'id_number' => 'رقم الهوية', 'id_issuer' => 'جهة الإصدار', 'setting' => 'الإعداد',
        'month' => 'الشهر', 'year' => 'السنة', 'type' => 'النوع', 'days' => 'الأيام',
    ];

    /** قيم enum → تسمية عربية، مفهرسة باسم الحقل. */
    private const VALUE_MAPS = [
        'status' => [
            'checked_in' => 'مسجّل دخول', 'checked_out' => 'غادر', 'cancelled' => 'ملغى',
            'reserved' => 'محجوزة', 'available' => 'متاحة', 'occupied' => 'مشغولة',
            'under_inspection' => 'تحت الفحص', 'maintenance' => 'صيانة',
            'draft' => 'مسودة', 'paid' => 'مدفوع', 'open' => 'مفتوح', 'locked' => 'مقفل',
            'present' => 'حاضر', 'absent' => 'غائب', 'late' => 'متأخر',
            'leave' => 'إجازة', 'holiday' => 'عطلة',
        ],
        'payment_status' => [
            'unpaid' => 'غير مدفوع', 'partial' => 'مدفوع جزئياً',
            'paid' => 'مدفوع بالكامل', 'deferred' => 'مؤجَّل',
        ],
        'method' => [
            'cash' => 'نقداً', 'bank_transfer' => 'تحويل بنكي', 'pos' => 'شبكة (POS)',
            'check' => 'شيك', 'credit_card' => 'بطاقة ائتمان', 'later' => 'آجل (لاحقاً)',
        ],
        'category' => [
            'maintenance' => 'صيانة', 'electricity' => 'كهرباء/مياه', 'salary' => 'رواتب',
            'cleaning' => 'نظافة', 'food' => 'طعام وشراب', 'other' => 'أخرى',
        ],
        'withdrawal_type'  => ['expense' => 'مصروف', 'currency_exchange' => 'صرف عملة'],
        'funding_source'   => ['shift' => 'من الوردية', 'general_safe' => 'من الصندوق العام'],
        'discount_type'    => ['percent' => 'نسبة مئوية', 'amount' => 'مبلغ ثابت'],
        'type'             => ['initial' => 'الحجز الأولي', 'renewal' => 'تجديد', 'damage' => 'تعويض أضرار',
                               'annual' => 'إجازة سنوية', 'sick' => 'مرضية', 'unpaid' => 'بدون راتب', 'emergency' => 'طارئة'],
        'segment_type'     => ['initial' => 'الحجز الأولي', 'renewal' => 'تجديد'],
        'id_type'          => ['passport' => 'جواز سفر', 'national_id' => 'هوية وطنية', 'residence' => 'إقامة'],
        'compensation_status' => ['none' => 'لا يوجد', 'pending' => 'غير محصَّل', 'paid' => 'محصَّل'],
    ];

    /** قيم الحقل الاصطناعي "action" المستخدَم في سجلات الإجراءات الخاصة. */
    private const ACTION_VALUES = [
        'damage_recorded'      => 'تسجيل أضرار',
        'damage_updated'       => 'تعديل أضرار',
        'segments_recomputed'  => 'إعادة احتساب فترات الغرفة',
        'segment_updated'      => 'تعديل فترة',
        'stay_dates_updated'   => 'تعديل تواريخ الإقامة',
        'reprice_from'         => 'تغيير السعر من تاريخ',
        'expense_settled'      => 'تسوية مصروف',
        'hotel_logo_updated'   => 'تحديث شعار الفندق',
        'hotel_logo_removed'   => 'حذف شعار الفندق',
        'hotel_profile_updated'=> 'تحديث بيانات الفندق',
    ];

    /** حقول تُعرَض كمبالغ مُنسَّقة. */
    private const MONEY_FIELDS = [
        'amount', 'total_amount', 'paid_amount', 'discount_amount', 'discount_value',
        'base_salary', 'net_salary', 'bonuses', 'deductions', 'withdrawals_deduction',
        'attendance_deduction', 'price_per_night', 'first_night_price',
        'renewal_price_per_night', 'compensation_amount', 'shortfall', 'delta',
        'total_received_yer', 'total_withdrawals_yer', 'exchange_to_amount',
    ];

    /** حقول تحمل معرّف مستخدم — تُستبدل باسمه. */
    private const USER_ID_FIELDS = [
        'created_by', 'cancelled_by', 'settled_by', 'processed_by', 'received_by',
        'paid_by', 'checked_out_by', 'inspected_by', 'added_by', 'user_id',
    ];

    public static function fieldLabel(string $key): string
    {
        return self::FIELD_LABELS[$key] ?? $key;
    }

    /** هل هذا الحقل ضجيج نظام لا يُعرض؟ */
    public static function isNoise(string $key): bool
    {
        return in_array($key, self::NOISE_FIELDS, true);
    }

    public static function formatValue(string $key, $value): string
    {
        if ($value === null || $value === '' || $value === []) {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'نعم' : 'لا';
        }
        if (is_array($value)) {
            return implode('، ', array_map(fn($v) => is_scalar($v) ? (string) $v : '…', $value));
        }

        // 0/1 في حقول منطقية معروفة
        if (in_array($key, ['is_active', 'is_closed', 'has_damage'], true)) {
            return $value ? 'نعم' : 'لا';
        }

        if ($key === 'action') {
            return self::ACTION_VALUES[$value] ?? (string) $value;
        }

        // معرّفات → أسماء مفهومة
        if ($resolved = self::resolveId($key, $value)) {
            return $resolved;
        }

        // قيم enum معروفة (payment_method يشارك خريطة method)
        $mapKey = $key === 'payment_method' ? 'method' : $key;
        if (isset(self::VALUE_MAPS[$mapKey][$value])) {
            return self::VALUE_MAPS[$mapKey][$value];
        }

        if (in_array($key, self::MONEY_FIELDS, true) && is_numeric($value)) {
            return number_format((float) $value, 0);
        }

        $s = (string) $value;
        return mb_strlen($s) > 80 ? mb_substr($s, 0, 80) . '…' : $s;
    }

    /** يحوّل معرّفاً رقمياً إلى اسم الكيان، أو null إن لم يكن حقل معرّف. */
    private static function resolveId(string $key, $value): ?string
    {
        if (! is_numeric($value)) {
            return null;
        }
        $id = (int) $value;

        if (in_array($key, self::USER_ID_FIELDS, true)) {
            return self::lookup('user', $id, fn() => User::find($id)?->name);
        }

        return match ($key) {
            'guest_id'       => self::lookup('guest', $id, fn() => Guest::find($id)?->full_name),
            'employee_id'    => self::lookup('employee', $id, fn() => Employee::find($id)?->name),
            'room_id',
            'linked_room_id' => self::lookup('room', $id, fn() => ($n = Room::find($id)?->room_number) ? 'غرفة ' . $n : null),
            'reservation_id' => 'حجز #' . $id,
            'shift_id'       => 'وردية #' . $id,
            'segment_id'     => 'فترة #' . $id,
            default          => null,
        };
    }

    private static function lookup(string $type, int $id, callable $resolver): ?string
    {
        $cacheKey = "{$type}.{$id}";
        if (! array_key_exists($cacheKey, self::$cache)) {
            try {
                self::$cache[$cacheKey] = $resolver();
            } catch (\Throwable $e) {
                self::$cache[$cacheKey] = null;
            }
        }

        return self::$cache[$cacheKey] ? self::$cache[$cacheKey] . ' (#' . $id . ')' : null;
    }
}
