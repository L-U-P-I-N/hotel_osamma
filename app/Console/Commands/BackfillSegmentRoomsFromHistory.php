<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Reservation;
use App\Models\ReservationSegment;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * تصحيح غرفة كل فترة محاسبة للحجوزات التي نُقل نزلاؤها بين غرف قبل إضافة عمود
 * room_id إلى reservation_segments — تلك الفترات مُلئت تلقائياً بغرفة الحجز
 * الحالية (خطأً) بواسطة migration الإضافة نفسها. هذا الأمر يستخدم سجل المراجعة
 * (AuditLog، action=room_transfer) — وهو المصدر الوحيد لتاريخ النقل الفعلي —
 * لإعادة تقسيم الفترات القديمة بحيث تحمل كل فترة الغرفة الصحيحة فعلياً في وقتها،
 * فيمكن لاحقاً طباعة فاتورة جزئية دقيقة لغرفة سابق أن نُقل عنها النزيل.
 *
 * آمن للتكرار: إعادة تشغيله بعد التصحيح لا يُغيّر شيئاً (نقاط الانكسار تقع عندها
 * بالفعل على حدود الفترات فلا يُعاد التقسيم).
 */
class BackfillSegmentRoomsFromHistory extends Command
{
    protected $signature = 'segments:backfill-rooms {--dry-run : عرض ما سيتغيّر دون حفظه}';

    protected $description = 'تصحيح غرفة فترات المحاسبة القديمة اعتماداً على سجل عمليات نقل الغرفة';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $reservationIds = AuditLog::where('model_type', Reservation::class)
            ->where('action', 'update')
            ->get(['model_id', 'old_values', 'new_values', 'created_at'])
            ->filter(fn (AuditLog $log) => ($log->new_values['action'] ?? null) === 'room_transfer'
                && isset($log->old_values['room_id'], $log->new_values['room_id']))
            ->pluck('model_id')
            ->unique();

        if ($reservationIds->isEmpty()) {
            $this->info('لا توجد عمليات نقل غرفة مسجَّلة في سجل المراجعة — لا شيء لتصحيحه.');
            return self::SUCCESS;
        }

        $this->info('معالجة ' . $reservationIds->count() . ' حجزاً له عمليات نقل غرفة...');

        $fixed = 0;
        $skipped = 0;

        foreach ($reservationIds as $reservationId) {
            $reservation = Reservation::with('segments')->find($reservationId);
            if (!$reservation || $reservation->segments->isEmpty()) {
                $skipped++;
                continue;
            }

            $logs = AuditLog::where('model_type', Reservation::class)
                ->where('model_id', $reservationId)
                ->where('action', 'update')
                ->get()
                ->filter(fn (AuditLog $log) => ($log->new_values['action'] ?? null) === 'room_transfer'
                    && isset($log->old_values['room_id'], $log->new_values['room_id']))
                ->sortBy('created_at')
                ->values();

            if ($logs->isEmpty()) {
                $skipped++;
                continue;
            }

            $breakpoints = collect([
                ['date' => Carbon::parse($reservation->check_in_date)->startOfDay(), 'room_id' => (int) $logs->first()->old_values['room_id']],
            ]);
            foreach ($logs as $log) {
                $breakpoints->push([
                    'date'    => Carbon::parse($log->created_at)->startOfDay(),
                    'room_id' => (int) $log->new_values['room_id'],
                ]);
            }
            // عند أكثر من نقل في نفس اليوم يُعتمَد آخر غرفة فقط لذلك اليوم
            $breakpoints = $breakpoints->groupBy(fn ($bp) => $bp['date']->toDateString())
                ->map(fn ($group) => $group->last())
                ->values()
                ->sortBy('date')
                ->values();

            $changed = $this->applyBreakpoints($reservation, $breakpoints, $dryRun);
            $changed ? $fixed++ : $skipped++;
        }

        $this->info(($dryRun ? '[تجربة] ' : '') . "اكتمل: صُحِّح {$fixed}، تُخطّي {$skipped}.");

        return self::SUCCESS;
    }

    private function applyBreakpoints(Reservation $reservation, $breakpoints, bool $dryRun): bool
    {
        $segments = $reservation->segments()->orderBy('start_date')->get();
        $changedAny = false;

        foreach ($segments as $segment) {
            $start = Carbon::parse($segment->start_date)->startOfDay();
            $end   = Carbon::parse($segment->end_date)->startOfDay();

            $roomBefore = $breakpoints->filter(fn ($bp) => $bp['date']->lte($start))
                ->sortByDesc('date')->first()['room_id'] ?? $segment->room_id;

            $inside = $breakpoints->filter(fn ($bp) => $bp['date']->gt($start) && $bp['date']->lt($end))
                ->sortBy('date')->values();

            if ($inside->isEmpty()) {
                if ((int) $segment->room_id !== (int) $roomBefore) {
                    $changedAny = true;
                    $this->line("  حجز #{$reservation->id} — فترة #{$segment->id}: غرفة {$segment->room_id} ← {$roomBefore}");
                    if (!$dryRun) {
                        $segment->update(['room_id' => $roomBefore]);
                    }
                }
                continue;
            }

            $changedAny = true;
            $price = (float) $segment->price_per_night;
            $pieces = [];
            $cursor = $start;
            $currentRoom = $roomBefore;
            foreach ($inside as $bp) {
                $pieces[] = ['start' => $cursor, 'end' => $bp['date'], 'room_id' => $currentRoom];
                $cursor = $bp['date'];
                $currentRoom = $bp['room_id'];
            }
            $pieces[] = ['start' => $cursor, 'end' => $end, 'room_id' => $currentRoom];

            $this->line("  حجز #{$reservation->id} — فترة #{$segment->id}: تُقسَم إلى " . count($pieces) . ' أجزاء بحسب النقل');

            if ($dryRun) {
                continue;
            }

            $first = $pieces[0];
            $firstNights = max(1, (int) $first['start']->diffInDays($first['end']));
            $segment->update([
                'room_id'  => $first['room_id'],
                'end_date' => $first['end']->toDateString(),
                'nights'   => $firstNights,
                'amount'   => round($price * $firstNights, 2),
            ]);

            foreach (array_slice($pieces, 1) as $piece) {
                $nights = max(1, (int) $piece['start']->diffInDays($piece['end']));
                ReservationSegment::create([
                    'reservation_id'  => $reservation->id,
                    'room_id'         => $piece['room_id'],
                    'type'            => 'renewal',
                    'start_date'      => $piece['start']->toDateString(),
                    'end_date'        => $piece['end']->toDateString(),
                    'nights'          => $nights,
                    'price_per_night' => $price,
                    'amount'          => round($price * $nights, 2),
                    'created_by'      => null,
                    'shift_id'        => $segment->shift_id,
                ]);
            }
        }

        return $changedAny;
    }
}
