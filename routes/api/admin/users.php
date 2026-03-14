<?php

use App\Http\Controllers\Api\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin User Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('users')->name('users.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Administration,create')->group(function () {
        Route::post('/', [UserController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Administration,update')->group(function () {
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Administration,delete')->group(function () {
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });
});
