<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Schedule::command('telescope:prune')->daily();

Schedule::call(function () {
    $files = Illuminate\Support\Facades\Storage::disk('public')->files('exports');
    $now = now()->timestamp;
    foreach ($files as $file) {
        if ($now - Illuminate\Support\Facades\Storage::disk('public')->lastModified($file) > 86400) {
            Illuminate\Support\Facades\Storage::disk('public')->delete($file);
        }
    }
})->daily();
