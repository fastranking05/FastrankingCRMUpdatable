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
        Route::get('/{id}', [ConsultationController::class, 'show'])->name('show');
        Route::get('/appointment/{appointmentId}', [ConsultationController::class, 'getByAppointment'])->name('get-by-appointment');
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
