<?php

use App\Http\Controllers\Api\Admin\BusinessCategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Business Category Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('business-categories')->name('business-categories.')->group(function () {
    Route::middleware('permission:Administration,read')->group(function () {
        Route::get('/', [BusinessCategoryController::class, 'index'])->name('index');
        Route::get('/{id}', [BusinessCategoryController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Administration,create')->group(function () {
        Route::post('/', [BusinessCategoryController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Administration,update')->group(function () {
        Route::put('/{id}', [BusinessCategoryController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Administration,delete')->group(function () {
        Route::delete('/{id}', [BusinessCategoryController::class, 'destroy'])->name('destroy');
    });
});
