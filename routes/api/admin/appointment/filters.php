<?php

use App\Http\Controllers\Api\Appointment\DirectAppointmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Appointments Filter Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('appointments')->name('appointments.')->group(function () {
    Route::middleware('permission:Appointment,read')->group(function () {
        // Filter endpoints
        Route::get('/filter-options', [DirectAppointmentController::class, 'getFilterOptions'])->name('filter-options');
        Route::post('/appointment-filter', [DirectAppointmentController::class, 'index'])->name('appointment-filter');
    });
});
