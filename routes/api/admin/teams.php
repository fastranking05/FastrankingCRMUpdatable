<?php

use App\Http\Controllers\Api\Admin\TeamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Team Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('teams')->name('teams.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::get('/{id}', [TeamController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Administration,create')->group(function () {
        Route::post('/', [TeamController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Administration,update')->group(function () {
        Route::put('/{id}', [TeamController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Administration,delete')->group(function () {
        Route::delete('/{id}', [TeamController::class, 'destroy'])->name('destroy');
    });
});
