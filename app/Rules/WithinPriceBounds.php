<?php
namespace App\Rules;

use App\Models\Room;
use App\Models\User;
use App\Services\PricingBoundsService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * قاعدة تحقق تُركَّب على أي حقل سعر ليلة، بحيث يُفرض نطاق المدير في كل
 * مسار يدخل منه مبلغ (حجز جديد، تعديل، تجديد، إعادة تسعير، مقاطع الإقامة).
 */
class WithinPriceBounds implements ValidationRule
{
    public function __construct(
        private ?Room $room,
        private User $user,
        private ?string $suiteBookingType = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $error = PricingBoundsService::validate($value, $this->room, $this->user, $this->suiteBookingType);

        if ($error !== null) {
            $fail($error);
        }
    }
}
