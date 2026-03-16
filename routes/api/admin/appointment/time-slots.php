<?php

use App\Http\Controllers\Api\Appointment\TimeSlotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Time Slot Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('time-slots')->name('time-slots.')->group(function () {
    Route::middleware('permission:Time Slot,read')->group(function () {
        Route::get('/', [TimeSlotController::class, 'index'])->name('index');
        Route::get('/{id}', [TimeSlotController::class, 'show'])->name('show');
        Route::get('/statistics', [TimeSlotController::class, 'getStatistics'])->name('statistics');
    });

    Route::middleware('permission:Time Slot,create')->group(function () {
        Route::post('/', [TimeSlotController::class, 'store'])->name('store');
        Route::post('/bulk', [TimeSlotController::class, 'bulkCreate'])->name('bulk');
    });

    Route::middleware('permission:Time Slot,update')->group(function () {
        Route::put('/{id}', [TimeSlotController::class, 'update'])->name('update');
        Route::patch('/{id}', [TimeSlotController::class, 'update'])->name('update.patch');
    });

    Route::middleware('permission:Time Slot,delete')->group(function () {
        Route::delete('/{id}', [TimeSlotController::class, 'destroy'])->name('destroy');
    });
});
