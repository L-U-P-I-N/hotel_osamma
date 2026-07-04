<?php

namespace App\Console\Commands;

use App\Models\Guest;
use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MergeDuplicateGuests extends Command
{
    protected $signature   = 'guests:merge-duplicates {--force : تنفيذ الدمج فعلياً بدل عرض النتائج فقط}';
    protected $description = 'دمج سجلات النزلاء المكررة (نفس رقم الهوية) الناتجة عن خلل تشفير سابق، مع الإبقاء على كامل البيانات';

    public function handle(): int
    {
        $dryRun = !$this->option('force');

        $duplicateHashes = Guest::whereNotNull('id_number_hash')
            ->select('id_number_hash')
            ->groupBy('id_number_hash')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('id_number_hash');

        if ($duplicateHashes->isEmpty()) {
            $this->info('لا توجد سجلات نزلاء مكررة.');
            return 0;
        }

        $this->info(($dryRun ? '[معاينة فقط] ' : '') . 'تم العثور على ' . $duplicateHashes->count() . ' مجموعة مكررة.');

        foreach ($duplicateHashes as $hash) {
            $guests = Guest::where('id_number_hash', $hash)->orderBy('created_at')->get();
            $canonical = $guests->first();
            $duplicates = $guests->slice(1);

            $this->line("• {$canonical->full_name} — الأساسي #{$canonical->id}، المكرر: " . $duplicates->pluck('id')->implode(', '));

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($canonical, $duplicates) {
                foreach ($duplicates as $dup) {
                    Reservation::where('guest_id', $dup->id)->update(['guest_id' => $canonical->id]);
                    $dup->delete();
                }
            });
        }

        if ($dryRun) {
            $this->warn('هذه معاينة فقط. لتنفيذ الدمج فعلياً شغّل الأمر مع --force');
        } else {
            $this->info('تم الدمج بنجاح.');
        }

        return 0;
    }
}
