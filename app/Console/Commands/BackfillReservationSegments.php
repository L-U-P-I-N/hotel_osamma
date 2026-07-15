<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Services\ReservationSegmentService;
use Illuminate\Console\Command;

/**
 * تعبئة سجل فترات الغرفة (الحجز الأولي + التجديدات) للحجوزات القائمة التي أُنشئت
 * قبل هذه الميزة — يعيد بناءها من تاريخ الحجز والملاحظات لعرض تفصيل أسعار الغرفة
 * بدل متوسط الليلة. آمن للتكرار: يتخطّى الحجوزات التي لها فترات مسجّلة مسبقاً.
 */
class BackfillReservationSegments extends Command
{
    protected $signature = 'reservations:backfill-segments {--force : إعادة البناء حتى لو وُجدت فترات مسبقاً}';

    protected $description = 'إعادة بناء فترات الغرفة (الأولي + التجديدات) للحجوزات القائمة';

    public function handle(ReservationSegmentService $service): int
    {
        $query = Reservation::withTrashed()->whereIn('status', ['checked_in', 'checked_out']);

        $total = $query->count();
        $done = 0;
        $skipped = 0;
        $failed = 0;

        $this->info("معالجة {$total} حجزاً...");

        $query->orderBy('id')->chunkById(100, function ($reservations) use ($service, &$done, &$skipped, &$failed) {
            foreach ($reservations as $reservation) {
                if (!$this->option('force') && $reservation->segments()->exists()) {
                    $skipped++;
                    continue;
                }

                if ($service->backfillFromHistory($reservation)) {
                    $done++;
                } else {
                    $failed++;
                    $this->warn("  تعذّر التوفيق للحجز #{$reservation->id} (يبقى العرض الاحتياطي)");
                }
            }
        });

        $this->info("اكتمل: أُعيد بناء {$done}، تُخطّي {$skipped}، تعذّر {$failed}.");

        return self::SUCCESS;
    }
}
