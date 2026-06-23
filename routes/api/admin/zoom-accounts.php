<?php

use App\Http\Controllers\Api\Admin\ZoomAccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Zoom Account Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('zoom-accounts')->name('zoom-accounts.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/', [ZoomAccountController::class, 'index'])->name('index');
        Route::get('/{id}', [ZoomAccountController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Administration,create')->group(function () {
        Route::post('/', [ZoomAccountController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Administration,update')->group(function () {
        Route::put('/{id}', [ZoomAccountController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Administration,delete')->group(function () {
        Route::delete('/{id}', [ZoomAccountController::class, 'destroy'])->name('destroy');
    });
});
