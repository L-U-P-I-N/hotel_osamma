<?php
namespace App\Services;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * مصدر الحقيقة الوحيد للتسعير: الموظف لا يرسل الإجمالي، بل يرسل سعر الليلة فقط،
 * والخادم هو من يتحقق من النطاق الذي حدده المدير ثم يحسب الإجمالي.
 */
class PricingService
{
    /** صلاحية إدخال سعر ليلة مختلف عن السعر الأساسي (ضمن النطاق) */
    public const PRICE_OVERRIDE_PERMISSION = 'room.price.edit';

    /**
     * مضاعف الوحدات: الجناح المزدوج أو الشقة المرتبطة دائماً يُحسبان بغرفتين.
     */
    public static function unitMultiplier(Room $room, ?string $suiteBookingType = null): int
    {
        if ($suiteBookingType === 'both' && $room->linked_room_id) {
            return 2;
        }
        if ($room->isApartment() && $room->is_always_linked && $room->linked_room_id) {
            return 2;
        }
        return 1;
    }

    /**
     * يتحقق من سعر الوحدة المطلوب ويعيد السعر الليلي الفعلي (بعد المضاعف).
     *
     * @param  float|string|null $requestedUnitPrice سعر الليلة للوحدة الواحدة كما أدخله الموظف
     * @throws ValidationException عند خروج السعر عن النطاق أو انعدام الصلاحية
     */
    public static function resolveNightlyPrice(
        Room $room,
        ?string $suiteBookingType,
        float|string|null $requestedUnitPrice,
        User $user,
        string $field = 'nightly_price'
    ): float {
        $type = $room->roomType;
        if (!$type) {
            throw ValidationException::withMessages([
                $field => 'لا يمكن تسعير هذه الغرفة لعدم ارتباطها بنوع غرفة.',
            ]);
        }

        $base = (float) $type->base_price;

        // لا سعر مُرسل (أو موظف بلا صلاحية التعديل) => السعر الأساسي المعتمد
        if ($requestedUnitPrice === null || $requestedUnitPrice === '') {
            $unitPrice = $base;
        } elseif (!$user->can(self::PRICE_OVERRIDE_PERMISSION)) {
            $requested = round((float) $requestedUnitPrice, 2);
            if (abs($requested - round($base, 2)) > 0.001) {
                throw ValidationException::withMessages([
                    $field => 'لا تملك صلاحية تعديل سعر الليلة. السعر المعتمد هو '
                              . number_format($base, 2) . ' ر.ي.',
                ]);
            }
            $unitPrice = $base;
        } else {
            $unitPrice = round((float) $requestedUnitPrice, 2);
        }

        if ($unitPrice <= 0) {
            throw ValidationException::withMessages([
                $field => 'سعر الليلة يجب أن يكون أكبر من صفر.',
            ]);
        }

        if (!$type->isPriceWithinBounds($unitPrice)) {
            throw ValidationException::withMessages([
                $field => 'سعر الليلة لـ "' . $type->name . '" يجب أن يكون بين '
                          . number_format($type->effective_min_price, 2) . ' و '
                          . number_format($type->effective_max_price, 2) . ' ر.ي.',
            ]);
        }

        return round($unitPrice * self::unitMultiplier($room, $suiteBookingType), 2);
    }

    /**
     * السعر الليلي المخزّن على حجز قائم، مع السقوط على سعر النوع للحجوزات القديمة.
     */
    public static function nightlyPriceFor(Reservation $reservation): float
    {
        $stored = (float) $reservation->nightly_price;
        if ($stored > 0) {
            return $stored;
        }

        $room = $reservation->room;
        $base = (float) ($room?->roomType?->base_price ?? 0);
        return round($base * self::unitMultiplier($room, $reservation->suite_booking_type), 2);
    }

    public static function totalFor(Reservation $reservation, int $nights): float
    {
        return round(self::nightlyPriceFor($reservation) * max(0, $nights), 2);
    }

    /** سقف نسبة الخصم الذي يحدده المدير (0 = الخصم موقوف) */
    public static function maxDiscountPercent(): float
    {
        return (float) (Hotel::query()->value('max_discount_percent') ?? 0);
    }

    /** أقصى مبلغ خصم مسموح على حجز: لا يتجاوز نسبة السقف ولا الرصيد المتبقي */
    public static function maxDiscountAmount(Reservation $reservation): float
    {
        $percent = self::maxDiscountPercent();
        if ($percent <= 0) {
            return 0.0;
        }

        $ceiling  = round($reservation->gross_amount * $percent / 100, 2);
        $remaining = max(0.0, $ceiling - (float) $reservation->discount_amount);

        return round(min($remaining, max(0.0, $reservation->balance)), 2);
    }

    /** نطاقات الأسعار لكل نوع غرفة — لتغذية واجهات الحجز */
    public static function boundsByRoomType(): array
    {
        return RoomType::all()->mapWithKeys(fn(RoomType $type) => [
            $type->id => [
                'base' => (float) $type->base_price,
                'min'  => $type->effective_min_price,
                'max'  => $type->effective_max_price,
            ],
        ])->toArray();
    }
}
