<?php

use Illuminate\Support\Facades\Route;

Route::domain('sollu.test')->group(function () {
    Route::get('/', function () {
        return 'Ini halaman utama';
    });
});
