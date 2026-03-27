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
        Route::post('/', [DirectAppointmentController::class, 'index'])->name('index');
        Route::post('/filter-options', [DirectAppointmentController::class, 'getFilterOptions'])->name('filter-options');
    });
});
