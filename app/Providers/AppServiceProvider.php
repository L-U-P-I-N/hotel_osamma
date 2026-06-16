<?php
namespace App\Providers;

use App\Models\Currency;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Observers\GuestObserver;
use App\Observers\PaymentObserver;
use App\Observers\ReservationObserver;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Reservation::observe(ReservationObserver::class);
        Guest::observe(GuestObserver::class);
        Payment::observe(PaymentObserver::class);

        View::composer('*', function ($view) {
            $globalCurrencies = Cache::remember('active_currencies', 3600, function () {
                return Currency::where('is_active', true)->orderBy('is_primary', 'desc')->orderBy('code')->get();
            });
            $view->with('globalCurrencies', $globalCurrencies);
        });

        // نظام الصلاحيات الديناميكي
        Gate::before(function ($user, string $ability) {
            $allowed = PermissionService::userCan($user, $ability);
            return $allowed ? true : null;
        });
    }
}
