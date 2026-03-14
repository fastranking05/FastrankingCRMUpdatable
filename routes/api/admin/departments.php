<?php

use App\Http\Controllers\Api\Admin\DepartmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Department Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('departments')->name('departments.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/', [DepartmentController::class, 'index'])->name('index');
        Route::get('/{id}', [DepartmentController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Administration,create')->group(function () {
        Route::post('/', [DepartmentController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Administration,update')->group(function () {
        Route::put('/{id}', [DepartmentController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Administration,delete')->group(function () {
        Route::delete('/{id}', [DepartmentController::class, 'destroy'])->name('destroy');
    });
});
