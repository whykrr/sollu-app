<?php

use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees')
    ->name('employees.')
    ->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/{user}', [EmployeeController::class, 'index'])->name('show');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::put('/{user}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{user}', [EmployeeController::class, 'delete'])->name('delete');
        Route::put('/{user}/restore', [EmployeeController::class, 'restore'])->name('restore')->withTrashed();
        Route::delete('/{user}/destroy', [EmployeeController::class, 'destroy'])->name('destroy')->withTrashed();
    });
