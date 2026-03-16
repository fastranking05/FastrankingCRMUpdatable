<?php

use App\Http\Controllers\Api\Appointment\TimeSlotPickerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Time Slot Picker Routes
|--------------------------------------------------------------------------
| These routes are completely public - no authentication or permissions required
| Loaded independently to avoid any global middleware
*/

Route::prefix('api/time-slots')->name('public.time-slots.')->group(function () {
    // Get available slots for specific date
    Route::get('/available', [TimeSlotPickerController::class, 'getAvailableSlotsByDate'])->name('available');
    
    // Get slots for date range (calendar view)
    Route::get('/range', [TimeSlotPickerController::class, 'getSlotsForDateRange'])->name('range');
    
    // Get next available slots
    Route::get('/next', [TimeSlotPickerController::class, 'getNextAvailableSlots'])->name('next');
});
