<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\ScheduledBackupJob;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily full backup at 2 AM
Schedule::job(new ScheduledBackupJob('full', true))
    ->dailyAt('02:00')
    ->name('daily-full-backup')
    ->description('Create daily full system backup');

// Schedule weekly database backup on Sundays at 3 AM
Schedule::job(new ScheduledBackupJob('database', false))
    ->weeklyOn(0, '03:00')
    ->name('weekly-database-backup')
    ->description('Create weekly database backup');

// Schedule monthly file backup on the 1st at 4 AM
Schedule::job(new ScheduledBackupJob('files', false))
    ->monthlyOn(1, '04:00')
    ->name('monthly-files-backup')
    ->description('Create monthly files backup');

// Schedule daily sales summary at midnight (00:00)
Schedule::command('telegram:daily-sales-summary')
    ->dailyAt('00:00')
    ->name('daily-sales-summary')
    ->description('Send daily sales summary to Telegram')
    ->appendOutputTo(storage_path('logs/scheduler.log'));
