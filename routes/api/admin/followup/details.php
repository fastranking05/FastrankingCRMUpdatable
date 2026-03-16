<?php

use App\Http\Controllers\Api\Followup\FollowupDetailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Follow-Up Details Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('followup-details')->name('followup-details.')->group(function () {
    Route::middleware('permission:Follow-Up,read')->group(function () {
        Route::get('/', [FollowupDetailController::class, 'index'])->name('index');
        Route::get('/{id}', [FollowupDetailController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Follow-Up,create')->group(function () {
        Route::post('/', [FollowupDetailController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Follow-Up,update')->group(function () {
        Route::put('/{id}', [FollowupDetailController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Follow-Up,delete')->group(function () {
        Route::delete('/{id}', [FollowupDetailController::class, 'destroy'])->name('destroy');
    });
});
