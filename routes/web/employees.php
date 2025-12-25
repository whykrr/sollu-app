<?php

use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::resource('employees', EmployeeController::class)->except(['edit'])
    ->parameters([
        'employees' => 'user',
    ])->withTrashed();
Route::put('employees/{user}/restore', [EmployeeController::class, 'restore'])
    ->name('employees.restore')
    ->withTrashed();
Route::delete('employees/{user}/purge', [EmployeeController::class, 'purge'])
    ->name('employees.purge')
    ->withTrashed();
