<?php
namespace App\Providers;

use App\Models\Guest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Observers\GuestObserver;
use App\Observers\PaymentObserver;
use App\Observers\ReservationObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Reservation::observe(ReservationObserver::class);
        Guest::observe(GuestObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
