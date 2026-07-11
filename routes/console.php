<?php

use App\Models\Setting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automatic backups, driven by the Settings module toggles.
// Requires the scheduler to be running (`php artisan schedule:work`
// locally, or a cron entry for `schedule:run` in production).
Schedule::command('backup:run --label=daily')->dailyAt('02:00')
    ->when(fn () => (bool) Setting::get('backups.auto_daily', false));

Schedule::command('backup:run --label=weekly')->weeklyOn(1, '02:30')
    ->when(fn () => (bool) Setting::get('backups.auto_weekly', false));

Schedule::command('backup:run --label=monthly')->monthlyOn(1, '03:00')
    ->when(fn () => (bool) Setting::get('backups.auto_monthly', false));
