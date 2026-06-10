<?php

use App\Http\Controllers\Api\Admin\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Service Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('services')->name('services.')->group(function () {
    // Read — open to all authenticated users
    Route::get('/', [ServiceController::class, 'index'])->name('index');
    Route::get('/{id}', [ServiceController::class, 'show'])->name('show');

    // Manage — requires Administration module permissions
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
