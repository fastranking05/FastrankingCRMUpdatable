<?php

use App\Http\Controllers\Api\Admin\BusinessTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Business Type Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('business-types')->name('business-types.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/', [BusinessTypeController::class, 'index'])->name('index');
        Route::get('/{id}', [BusinessTypeController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Administration,create')->group(function () {
        Route::post('/', [BusinessTypeController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Administration,update')->group(function () {
        Route::put('/{id}', [BusinessTypeController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Administration,delete')->group(function () {
        Route::delete('/{id}', [BusinessTypeController::class, 'destroy'])->name('destroy');
    });
});
