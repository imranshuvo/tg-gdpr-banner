<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule license monitoring daily at 9 AM
Schedule::command('licenses:monitor --send-alerts')->dailyAt('09:00');

// Clean up old activity logs (older than 90 days)
Schedule::command('model:prune', [
    '--model' => [\App\Models\ActivityLog::class],
])->daily();
