<?php

use App\Http\Controllers\Api\Admin\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Role Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('roles')->name('roles.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/{id}', [RoleController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Administration,create')->group(function () {
        Route::post('/', [RoleController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Administration,update')->group(function () {
        Route::put('/{id}', [RoleController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Administration,delete')->group(function () {
        Route::delete('/{id}', [RoleController::class, 'destroy'])->name('destroy');
    });
});
