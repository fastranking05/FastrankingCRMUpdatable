<?php

use App\Http\Controllers\Api\Admin\BusinessTypeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Business Type Routes
|--------------------------------------------------------------------------
*/

// Route::middleware(['jwt.auth'])->prefix('business-types')->name('business-types.')->group(function () {
//     Route::middleware('permission:Administration,create')->group(function () {
//         Route::get('/', [BusinessTypeController::class, 'index'])->name('index');
//         Route::post('/', [BusinessTypeController::class, 'store'])->name('store');
//         Route::get('/{id}', [BusinessTypeController::class, 'show'])->name('show');
//         Route::put('/{id}', [BusinessTypeController::class, 'update'])->name('update');
//         Route::delete('/{id}', [BusinessTypeController::class, 'destroy'])->name('destroy');
//     });
// });

Route::middleware(['jwt.auth'])->prefix('business-types')->name('business-types.')->group(function () {
        Route::get('/', [BusinessTypeController::class, 'index'])->name('index');
        Route::post('/', [BusinessTypeController::class, 'store'])->name('store');
        Route::get('/{id}', [BusinessTypeController::class, 'show'])->name('show');
        Route::put('/{id}', [BusinessTypeController::class, 'update'])->name('update');
        Route::delete('/{id}', [BusinessTypeController::class, 'destroy'])->name('destroy');
});
