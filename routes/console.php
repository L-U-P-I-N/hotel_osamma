<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// نسخة احتياطية يومية (بيانات فقط) — بلا تراكب مع نسخة أخرى قيد التنفيذ،
// وتُسجَّل مخرجاتها في ملف سجلّ مخصَّص للتشخيص عند فشل الرفع (انقطاع إنترنت مثلاً).
Schedule::command('hotel:backup')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));

// نسخة أسبوعية كاملة تشمل ملفات النزلاء والسندات (هويات، صور أضرار، سندات تحويل)
Schedule::command('hotel:backup --files')
    ->weeklyOn(5, '03:30') // الجمعة 03:30
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/backup.log'));
