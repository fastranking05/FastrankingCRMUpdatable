<?php

use App\Http\Controllers\Api\Followup\FollowupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Complete Follow-Up Routes (Unified API)
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('followup')->name('followup.')->group(function () {
    Route::middleware('permission:Follow-Up,read')->group(function () {
        Route::get('/', [FollowupController::class, 'index'])->name('index');
        
        // New followup view APIs (must come before /{id} route)
        Route::get('/my', [FollowupController::class, 'myFollowups'])->name('my');
        Route::get('/today', [FollowupController::class, 'todaysFollowups'])->name('today');
        
        // Parameterized routes (must come after specific routes)
        Route::get('/{id}', [FollowupController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Follow-Up,create')->group(function () {
        Route::post('/', [FollowupController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Follow-Up,update')->group(function () {
        Route::put('/{id}', [FollowupController::class, 'update'])->name('update');
        Route::patch('/{id}', [FollowupController::class, 'update'])->name('update.patch');
    });

    Route::middleware('permission:Follow-Up,delete')->group(function () {
        Route::delete('/{id}', [FollowupController::class, 'destroy'])->name('destroy');
    });
});
