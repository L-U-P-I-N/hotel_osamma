<?php
namespace App\Observers;

use App\Models\Reservation;
use App\Services\AuditLogService;

class ReservationObserver
{
    public function created(Reservation $reservation): void
    {
        AuditLogService::log('create', $reservation, null, $reservation->toArray());
    }

    public function updated(Reservation $reservation): void
    {
        $changed = $reservation->getDirty();
        if (isset($changed['status']) || isset($changed['payment_status'])) {
            AuditLogService::log('update', $reservation, $reservation->getOriginal(), $reservation->toArray());
        }
    }
}
