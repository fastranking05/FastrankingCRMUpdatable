<?php

use App\Http\Controllers\Api\Consultation\ConsultationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Consultation Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('consultation')->name('consultation.')->group(function () {
    // Consultation CRUD Routes
    Route::middleware('permission:Consultation,read')->group(function () {
        Route::get('/', [ConsultationController::class, 'index'])->name('index');
        Route::get('/filter', [ConsultationController::class, 'filter'])->name('filter');
        Route::get('/filter-options', [ConsultationController::class, 'getFilterOptions'])->name('filter-options');

        // New consultation status-based routes (must come before /{id} route)
        Route::get('/scheduled', [ConsultationController::class, 'getScheduledConsultations'])->name('scheduled');
        Route::get('/conducted', [ConsultationController::class, 'getConductedConsultations'])->name('conducted');
        Route::get('/not-conducted', [ConsultationController::class, 'getNotConductedConsultations'])->name('not-conducted');
        Route::get('/today', [ConsultationController::class, 'getTodayConsultations'])->name('today');
        Route::get('/appointment/{appointmentId}', [ConsultationController::class, 'getByAppointment'])->name('get-by-appointment');

        // Parameterized routes (must come after specific routes)
        Route::get('/{id}', [ConsultationController::class, 'show'])->name('show');
    });

    Route::middleware('permission:Consultation,create')->group(function () {
        Route::post('/', [ConsultationController::class, 'store'])->name('store');
    });

    Route::middleware('permission:Consultation,update')->group(function () {
        Route::put('/{id}', [ConsultationController::class, 'update'])->name('update');
        Route::post('/{id}/close', [ConsultationController::class, 'closeConsultation'])->name('close');
    });

    Route::middleware('permission:Consultation,delete')->group(function () {
        Route::delete('/{id}', [ConsultationController::class, 'destroy'])->name('destroy');
    });
});
