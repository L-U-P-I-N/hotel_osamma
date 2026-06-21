<?php
namespace App\Observers;

use App\Models\CashWithdrawal;
use App\Services\ShiftService;

class CashWithdrawalObserver
{
    public function created(CashWithdrawal $withdrawal): void
    {
        if ($withdrawal->shift_id) {
            app(ShiftService::class)->computeTotals($withdrawal->shift);
        }
    }

    public function updated(CashWithdrawal $withdrawal): void
    {
        $oldShiftId = $withdrawal->getOriginal('shift_id');
        $newShiftId = $withdrawal->shift_id;

        if ($oldShiftId && $oldShiftId !== $newShiftId) {
            $oldShift = \App\Models\Shift::find($oldShiftId);
            if ($oldShift) {
                app(ShiftService::class)->computeTotals($oldShift);
            }
        }

        if ($newShiftId) {
            app(ShiftService::class)->computeTotals($withdrawal->shift);
        }
    }

    public function deleted(CashWithdrawal $withdrawal): void
    {
        if ($withdrawal->shift_id) {
            $shift = \App\Models\Shift::find($withdrawal->shift_id);
            if ($shift) {
                app(ShiftService::class)->computeTotals($shift);
            }
        }
    }
}
