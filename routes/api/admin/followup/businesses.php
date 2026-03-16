<?php

use App\Http\Controllers\Api\Followup\FollowupBusinessController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Follow-Up Business Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('followup-businesses')->name('followup-businesses.')->group(function () {
    Route::middleware('permission:Follow-Up,read')->group(function () {
        Route::get('/', [FollowupBusinessController::class, 'index'])->name('index');
        Route::get('/{id}', [FollowupBusinessController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Follow-Up,create')->group(function () {
        Route::post('/', [FollowupBusinessController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Follow-Up,update')->group(function () {
        Route::put('/{id}', [FollowupBusinessController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Follow-Up,delete')->group(function () {
        Route::delete('/{id}', [FollowupBusinessController::class, 'destroy'])->name('destroy');
    });
});
