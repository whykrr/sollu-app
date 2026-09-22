<?php

use App\Enums\FeatureEnum;
use App\Http\Controllers\App\Employee\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::prefix('employees')
    ->name('employees.')
    ->middleware('plan.feature:'.FeatureEnum::EMPLOYEE_MANAGEMENT->value)
    ->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('/export', [EmployeeController::class, 'export'])->name('export');
        Route::get('/import/template', [EmployeeController::class, 'importTemplate'])->name('importTemplate');
        Route::post('/import', [EmployeeController::class, 'import'])->name('import');
        Route::get('/{user}', [EmployeeController::class, 'show'])->name('show');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::put('/{user}', [EmployeeController::class, 'update'])->name('update');
        Route::delete('/{user}', [EmployeeController::class, 'delete'])->name('delete');
        Route::put('/{user}/restore', [EmployeeController::class, 'restore'])->name('restore')->withTrashed();
        Route::delete('/{user}/destroy', [EmployeeController::class, 'destroy'])->name('destroy')->withTrashed();
    });
