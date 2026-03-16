<?php

use App\Http\Controllers\Api\Followup\FollowupAuthPersonController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Follow-Up Auth Persons Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('followup-auth-persons')->name('followup-auth-persons.')->group(function () {
    Route::middleware('permission:Follow-Up,read')->group(function () {
        Route::get('/', [FollowupAuthPersonController::class, 'index'])->name('index');
        Route::get('/{id}', [FollowupAuthPersonController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Follow-Up,create')->group(function () {
        Route::post('/', [FollowupAuthPersonController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Follow-Up,update')->group(function () {
        Route::put('/{id}', [FollowupAuthPersonController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Follow-Up,delete')->group(function () {
        Route::delete('/{id}', [FollowupAuthPersonController::class, 'destroy'])->name('destroy');
    });
});
