<?php

use App\Http\Controllers\Api\Appointment\AppointmentController;
use App\Http\Controllers\Api\Appointment\DirectAppointmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Appointment Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('appointments')->name('appointments.')->group(function () {
    Route::middleware('permission:Appointment,read')->group(function () {
        // Main appointment routes using business_id
        Route::get('/', [DirectAppointmentController::class, 'index'])->name('index');
        
        // Legacy appointment routes
        Route::get('/{id}', [AppointmentController::class, 'show'])->name('show');
        Route::get('/slots/available', [AppointmentController::class, 'getAvailableSlots'])->name('slots.available');
        Route::get('/available-slots', [DirectAppointmentController::class, 'getAvailableTimeSlots'])->name('available-slots');
        Route::get('/direct/{appointmentId}', [DirectAppointmentController::class, 'show'])->name('direct.show');
    });

    Route::middleware('permission:Appointment,create')->group(function () {
        // Main appointment creation using business_id
        Route::post('/', [DirectAppointmentController::class, 'createDirectAppointment'])->name('create');
        
        // Legacy appointment routes
        Route::post('/slots/hold', [AppointmentController::class, 'holdTimeSlot'])->name('slots.hold');
        Route::post('/slots/confirm', [AppointmentController::class, 'confirmAppointment'])->name('slots.confirm');
        Route::post('/direct', [DirectAppointmentController::class, 'createDirectAppointment'])->name('direct.create');
        Route::post('/business/{businessId}', [DirectAppointmentController::class, 'createAppointmentForExistingBusiness'])->name('create-for-business');
        Route::post('/hold-slot', [DirectAppointmentController::class, 'holdTimeSlot'])->name('hold-slot');
        Route::post('/release-slot', [DirectAppointmentController::class, 'releaseTimeSlot'])->name('release-slot');
    });

    Route::middleware('permission:Appointment,update')->group(function () {
        // Main appointment update using business_id
        Route::put('/{business_id}', [DirectAppointmentController::class, 'updateAppointmentByBusiness'])->name('update');
        
        // Legacy appointment routes
        Route::put('/{id}', [AppointmentController::class, 'update'])->name('update.legacy');
        Route::patch('/{id}', [AppointmentController::class, 'update'])->name('update.patch');
        Route::put('/direct/{appointmentId}', [DirectAppointmentController::class, 'update'])->name('direct.update');
    });

    Route::middleware('permission:Appointment,delete')->group(function () {
        // Main appointment delete using business_id
        Route::delete('/{business_id}', [DirectAppointmentController::class, 'deleteAppointmentByBusiness'])->name('delete');
        
        // Legacy appointment routes
        Route::delete('/{id}', [AppointmentController::class, 'destroy'])->name('destroy');
        Route::delete('/slots/release', [AppointmentController::class, 'releaseTimeSlot'])->name('slots.release');
        Route::delete('/direct/{appointmentId}/cancel', [DirectAppointmentController::class, 'cancel'])->name('direct.cancel');
    });
});
