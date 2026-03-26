<?php

use App\Http\Controllers\Api\Followup\FollowupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Followup Filter Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('followup')->name('followup.')->group(function () {
    Route::middleware('permission:Follow-Up,read')->group(function () {
        // Filter endpoints
        Route::get('/', [FollowupController::class, 'index'])->name('index');
        Route::get('/filter-options', [FollowupController::class, 'getFilterOptions'])->name('filter-options');
    });
});
