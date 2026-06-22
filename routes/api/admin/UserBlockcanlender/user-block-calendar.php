<?php

use App\Http\Controllers\Api\UserBlockcanlender\UserBlockCalendarController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Block Calendar Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('user-block-calendar')->name('user-block-calendar.')->group(function () {
    Route::middleware('permission:Appointment,read')->group(function () {
        Route::get('/available-slots', [UserBlockCalendarController::class, 'getAvailableTimeSlots'])->name('available-slots');
        Route::get('/schedule-details', [UserBlockCalendarController::class, 'getScheduleDetails'])->name('schedule-details');
        Route::get('/', [UserBlockCalendarController::class, 'index'])->name('index');
        Route::get('/{id}', [UserBlockCalendarController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Appointment,create')->group(function () {
        Route::post('/', [UserBlockCalendarController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Appointment,update')->group(function () {
        Route::put('/{id}', [UserBlockCalendarController::class, 'update'])->name('update');
    });

    Route::middleware('permission:Appointment,delete')->group(function () {
        Route::delete('/{id}', [UserBlockCalendarController::class, 'destroy'])->name('destroy');
    });
});
