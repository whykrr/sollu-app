<?php

use Illuminate\Support\Facades\Route;

Route::prefix('template')->name('template.')->group(function () {
    Route::get('/form', function () {
        return inertia('Dashboard/Template/Form');
    })->name('form');

    Route::get('/cards', function () {
        return inertia('Dashboard/Template/Cards');
    })->name('cards');

    Route::get('/navigation', function () {
        return inertia('Dashboard/Template/Navigation');
    })->name('navigation');

    Route::get('/buttons', function () {
        return inertia('Dashboard/Template/Buttons');
    })->name('buttons');

    Route::get('/charts', function () {
        return inertia('Dashboard/Template/Charts');
    })->name('charts');

    Route::get('/notifications', function () {
        return inertia('Dashboard/Template/Notifications');
    })->name('notifications');

    Route::get('/widgets', function () {
        return inertia('Dashboard/Template/Widgets');
    })->name('widgets');
});
