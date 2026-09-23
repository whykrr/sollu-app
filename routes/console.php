<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Horizon snapshot every 5 minutes for Redis queue metrics
Schedule::command('horizon:snapshot')->everyFiveMinutes();

// Pulse metrics retention (14 days auto-pruning)
Schedule::command('pulse:clear --days='.(int) env('PULSE_PRUNE_DAYS', 14))->daily();

// Telescope pruning (strictly non-production / development)
if (! app()->isProduction() && class_exists(\Laravel\Telescope\Telescope::class)) {
    Schedule::command('telescope:prune --hours='.(int) env('TELESCOPE_PRUNE_HOURS', 24))->daily();
}

// Prune expired export & orphaned import files (hourly execution on production)
Schedule::command('exports:prune --hours=24')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

Schedule::command('subscription:renewal-notification')->dailyAt('08:00');
Schedule::command('audit:manage-partitions --prune-days=365')->dailyAt('02:00');
Schedule::command('notifications:prune --days=365')->dailyAt('02:30');
