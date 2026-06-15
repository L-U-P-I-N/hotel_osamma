<?php
namespace App\Observers;

use App\Models\Guest;
use App\Services\AuditLogService;

class GuestObserver
{
    public function updated(Guest $guest): void
    {
        $changed = $guest->getDirty();
        if (isset($changed['is_blacklisted'])) {
            AuditLogService::log('update', $guest, $guest->getOriginal(), $guest->toArray());
        }
    }
}
