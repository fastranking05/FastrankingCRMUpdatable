<?php

use App\Http\Controllers\Api\Appointment\TimeSlotPickerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Time Slot Picker Routes
|--------------------------------------------------------------------------
| Simple endpoints for frontend time slot selection
*/

Route::prefix('time-slots')->name('time-slots.')->group(function () {
    // Get available slots for specific date
    Route::get('/available', [TimeSlotPickerController::class, 'getAvailableSlotsByDate'])->name('available');
    
    // Get slots for date range (calendar view)
    Route::get('/range', [TimeSlotPickerController::class, 'getSlotsForDateRange'])->name('range');
    
    // Get next available slots
    Route::get('/next', [TimeSlotPickerController::class, 'getNextAvailableSlots'])->name('next');
});
