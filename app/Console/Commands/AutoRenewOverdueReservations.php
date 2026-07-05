<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\AuditLogService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoRenewOverdueReservations extends Command
{
    protected $signature   = 'reservations:auto-renew';
    protected $description = 'تجديد تلقائي للحجوزات النشطة التي تجاوز وقتها موعد المغادرة دون تسجيل خروج';

    public function handle(): int
    {
        $now = now();

        $reservations = Reservation::where('status', 'checked_in')->get();

        $renewedCount = 0;

        foreach ($reservations as $reservation) {
            $cutoffTime = $reservation->check_out_time ?: '13:00';
            $cutoff = Carbon::parse($reservation->check_out_date->toDateString() . ' ' . $cutoffTime);

            if ($now->lt($cutoff)) {
                continue; // لم يحن موعد المغادرة بعد
            }

            $oldCheckOut = $reservation->check_out_date->copy();
            $newCheckOut = $oldCheckOut->copy();
            // نُقدّم تاريخ المغادرة يوماً بيوم حتى يصبح موعدها القادم في المستقبل —
            // يُعالج بشكل صحيح تراكم عدة أيام فائتة إن توقف الجدولة الدورية مؤقتاً
            while ($now->gte(Carbon::parse($newCheckOut->toDateString() . ' ' . $cutoffTime))) {
                $newCheckOut->addDay();
            }

            $extraNights = $oldCheckOut->diffInDays($newCheckOut);
            if ($extraNights <= 0) {
                continue;
            }

            $pricePerNight = $reservation->effective_renewal_price_per_night;
            $extraAmount = $extraNights * $pricePerNight;

            $old = $reservation->only(['check_out_date', 'total_amount']);

            $note = "[تجديد تلقائي +{$extraNights} ليلة — {$now->format('Y-m-d H:i')}] لم يُسجَّل خروج النزيل عند موعد المغادرة، فامتدت الإقامة تلقائياً";

            $reservation->update([
                'check_out_date'           => $newCheckOut->toDateString(),
                'total_amount'             => $reservation->total_amount + $extraAmount,
                'notes'                    => $reservation->notes ? $reservation->notes . "\n" . $note : $note,
                'auto_renewed_at'          => $now,
                'auto_renew_extra_nights' => $extraNights,
                'auto_renew_acknowledged'  => false,
            ]);

            AuditLogService::log('update', $reservation, $old, [
                'check_out_date' => $newCheckOut->toDateString(),
                'extra_nights'   => $extraNights,
                'extra_amount'   => $extraAmount,
                'auto'           => true,
            ]);

            $renewedCount++;
            $this->line("  ✓ حجز #{$reservation->id} — تجديد تلقائي +{$extraNights} ليلة حتى {$newCheckOut->toDateString()}");
        }

        $this->info("تم تجديد {$renewedCount} حجز تلقائياً.");
        return 0;
    }
}
