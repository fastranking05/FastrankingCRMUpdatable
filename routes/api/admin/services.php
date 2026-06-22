<?php

use App\Http\Controllers\Api\Admin\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Service Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('services')->name('services.')->group(function () {
    Route::middleware('any.module.read')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::get('/{id}', [ServiceController::class, 'show'])->name('show');
    });
    Route::middleware('permission:Administration,create')->group(function () {
        Route::post('/', [ServiceController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Administration,update')->group(function () {
        Route::put('/{id}', [ServiceController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Administration,delete')->group(function () {
        Route::delete('/{id}', [ServiceController::class, 'destroy'])->name('destroy');
    });
});
