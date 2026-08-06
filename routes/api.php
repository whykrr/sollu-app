<?php

use App\Http\Controllers\API\Midtrans\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('midtrans/notification', NotificationController::class)->name('midtrans.notification');
