<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// نسخ احتياطي يومي للقاعدة الساعة 2:00 صباحاً
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onFailure(fn() => logger()->error('فشل النسخ الاحتياطي اليومي'))
    ->onSuccess(fn() => logger()->info('تم النسخ الاحتياطي بنجاح'));

// تنظيف النسخ القديمة يومياً الساعة 2:30 صباحاً
Schedule::command('backup:clean')
    ->dailyAt('02:30')
    ->withoutOverlapping();
