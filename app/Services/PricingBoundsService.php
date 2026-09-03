<?php
namespace App\Services;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;

/**
 * يفرض نطاق السعر الذي حدده المدير على كل مبلغ ليلة يدخله الموظف.
 *
 * القاعدة: النطاق معرّف لكل نوع غرفة على أساس القسم الواحد. حجز الجناح
 * كاملاً (A+B) يُضاعَف حده الأدنى والأعلى، تماماً كما أن سعره الافتراضي
 * هو مجموع سعرَي القسمين.
 */
class PricingBoundsService
{
    /** صلاحية إدخال سعر مختلف عن السعر الافتراضي للغرفة */
    public const PRICE_OVERRIDE_PERMISSION = 'room.price.edit';

    /** هل هذا الحجز للجناح كاملاً (غرفتان) بدل قسم واحد؟ */
    public static function isFullSuite(?string $suiteBookingType): bool
    {
        return $suiteBookingType === 'both';
    }

    /**
     * [min, max] لسعر الليلة المسموح.
     *
     * قسم الجناح غرفة كأي غرفة، فيأخذ نطاق القسم الواحد. أما الجناح كاملاً
     * فله نطاقه المستقل الذي يضبطه المدير — لا ضِعف نطاق القسم — حتى يمكن
     * بيعه بسعر عرض يختلف عن مجموع قسميه.
     */
    public static function boundsFor(?Room $room, ?string $suiteBookingType = null): ?array
    {
        $type = $room?->roomType;
        if (!$type) {
            return null;
        }

        if (self::isFullSuite($suiteBookingType)) {
            return [$type->effective_suite_min_price, $type->effective_suite_max_price];
        }

        return [$type->effective_min_price, $type->effective_max_price];
    }

    /** السعر الافتراضي المقترح للغرفة (وهو المسموح الوحيد لمن لا يملك صلاحية التعديل) */
    public static function defaultPriceFor(Room $room, ?string $suiteBookingType = null): float
    {
        if ($suiteBookingType === 'both') {
            return round($room->fullSuitePrice(), 2);
        }

        return round($room->priceFor('YER'), 2);
    }

    /**
     * يتحقق من سعر ليلة واحد ويعيد رسالة الخطأ بالعربية، أو null إذا كان مقبولاً.
     * قيمة فارغة تعني "لم يُرسل سعر" ولا يُعترض عليها هنا.
     */
    public static function validate(
        float|string|null $price,
        ?Room $room,
        User $user,
        ?string $suiteBookingType = null
    ): ?string {
        if ($price === null || $price === '') {
            return null;
        }

        $price = round((float) $price, 2);
        $type  = $room?->roomType;

        if (!$room || !$type) {
            return null; // بلا نوع غرفة لا يوجد نطاق نقيس عليه
        }

        // موظف بلا صلاحية تعديل السعر: مسموح له فقط بالسعر الافتراضي للغرفة
        if (!$user->can(self::PRICE_OVERRIDE_PERMISSION)) {
            $default = self::defaultPriceFor($room, $suiteBookingType);
            if (abs($price - $default) > 0.009) {
                return 'لا تملك صلاحية تعديل سعر الليلة. السعر المعتمد لهذه الغرفة هو '
                       . number_format($default, 0) . ' ر.ي.';
            }
            return null;
        }

        [$min, $max] = self::boundsFor($room, $suiteBookingType);

        if ($price < $min || $price > $max) {
            $label = self::isFullSuite($suiteBookingType)
                ? '"' . $type->name . '" كاملاً (غرفتان)'
                : '"' . $type->name . '"';

            return 'سعر الليلة لـ ' . $label . ' يجب أن يكون بين '
                   . number_format($min, 0) . ' و ' . number_format($max, 0) . ' ر.ي.';
        }

        return null;
    }

    /** نطاقات كل الأنواع — لتغذية واجهات الحجز بحدود الإدخال */
    public static function boundsByRoomType(): array
    {
        return RoomType::all()->mapWithKeys(fn(RoomType $type) => [
            $type->id => [
                'min'       => $type->effective_min_price,
                'max'       => $type->effective_max_price,
                'suite_min' => $type->effective_suite_min_price,
                'suite_max' => $type->effective_suite_max_price,
            ],
        ])->toArray();
    }
}
