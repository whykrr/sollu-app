<?php

use App\Http\Controllers\API\Midtrans\NotificationController;

Route::post('midtrans/notification', NotificationController::class)->name('midtrans.notification');
