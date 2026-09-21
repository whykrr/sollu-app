<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('telescope:prune --hours='.(int) env('TELESCOPE_PRUNE_HOURS', 24))->daily();

Schedule::call(function () {
    $files = Illuminate\Support\Facades\Storage::disk('public')->files('exports');
    $now = now()->timestamp;
    foreach ($files as $file) {
        if ($now - Illuminate\Support\Facades\Storage::disk('public')->lastModified($file) > 86400) {
            Illuminate\Support\Facades\Storage::disk('public')->delete($file);
        }
    }
})->daily();

Schedule::command('subscription:renewal-notification')->dailyAt('08:00');
Schedule::command('notifications:prune --days=365')->dailyAt('02:30');
