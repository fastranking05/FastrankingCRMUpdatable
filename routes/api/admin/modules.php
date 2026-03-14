<?php

use App\Http\Controllers\Api\Admin\ModuleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('modules')->name('modules.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/', [ModuleController::class, 'index'])->name('index');
        Route::get('/{id}', [ModuleController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Administration,create')->group(function () {
        Route::post('/', [ModuleController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Administration,update')->group(function () {
        Route::put('/{id}', [ModuleController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Administration,delete')->group(function () {
        Route::delete('/{id}', [ModuleController::class, 'destroy'])->name('destroy');
    });
});
