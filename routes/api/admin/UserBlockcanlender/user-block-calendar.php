<?php

use App\Http\Controllers\Api\UserBlockcanlender\UserBlockCalendarController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Block Calendar Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('user-block-calendar')->name('user-block-calendar.')->group(function () {
    // User Block Calendar Routes
    Route::get('/available-slots', [UserBlockCalendarController::class, 'getAvailableTimeSlots'])->name('available-slots');
    Route::get('/schedule-details', [UserBlockCalendarController::class, 'getScheduleDetails'])->name('schedule-details');
    Route::get('/', [UserBlockCalendarController::class, 'index'])->name('index');
    Route::get('/{id}', [UserBlockCalendarController::class, 'show'])->name('show');
    Route::post('/', [UserBlockCalendarController::class, 'store'])->name('store');
    Route::put('/{id}', [UserBlockCalendarController::class, 'update'])->name('update');
    Route::delete('/{id}', [UserBlockCalendarController::class, 'destroy'])->name('destroy');
});
