<?php

use Illuminate\Support\Facades\Route;

Route::prefix('template')->name('template.')->group(function () {
    Route::get('/form', function () {
        return inertia('Template/Form');
    })->name('form');

    Route::get('/cards', function () {
        return inertia('Template/Cards');
    })->name('cards');

    Route::get('/navigation', function () {
        return inertia('Template/Navigation');
    })->name('navigation');

    Route::get('/buttons', function () {
        return inertia('Template/Buttons');
    })->name('buttons');

    Route::get('/charts', function () {
        return inertia('Template/Charts');
    })->name('charts');

    Route::get('/notifications', function () {
        return inertia('Template/Notifications');
    })->name('notifications');

    Route::get('/widgets', function () {
        return inertia('Template/Widgets');
    })->name('widgets');
});
