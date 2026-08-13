<?php

use App\Http\Controllers\PromotionController;
use Illuminate\Support\Facades\Route;

Route::prefix('promotions')->name('promotions.')->group(function () {
    Route::post('/{promotion}/publish', [PromotionController::class, 'publish'])->name('publish');
    Route::post('/{promotion}/unpublish', [PromotionController::class, 'unpublish'])->name('unpublish');
});

Route::resource('promotions', PromotionController::class)->except(['create', 'edit', 'show']);
