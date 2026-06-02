<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cleanup old ZIP downloads daily at 3 AM
Schedule::command('download:cleanup --hours=24')->dailyAt('03:00');

// Cleanup old temp files every 6 hours (older than 30 minutes)
Schedule::command('temp:cleanup --minutes=30')->everySixHours();
