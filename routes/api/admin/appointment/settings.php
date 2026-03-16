<?php

use App\Http\Controllers\Api\Appointment\AppointmentSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Appointment Settings Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['jwt.auth'])->prefix('appointment-settings')->name('appointment-settings.')->group(function () {
    Route::middleware('permission:Appointment Settings,read')->group(function () {
        Route::get('/', [AppointmentSettingController::class, 'index'])->name('index');
        Route::get('/{key}', [AppointmentSettingController::class, 'show'])->name('show');
        Route::get('/summary', [AppointmentSettingController::class, 'getSummary'])->name('summary');
    });

    Route::middleware('permission:Appointment Settings,create')->group(function () {
        Route::post('/', [AppointmentSettingController::class, 'store'])->name('store');
        Route::post('/initialize', [AppointmentSettingController::class, 'initializeDefaults'])->name('initialize');
    });

    Route::middleware('permission:Appointment Settings,update')->group(function () {
        Route::put('/{key}', [AppointmentSettingController::class, 'update'])->name('update');
        Route::patch('/{key}', [AppointmentSettingController::class, 'update'])->name('update.patch');
    });

    Route::middleware('permission:Appointment Settings,delete')->group(function () {
        Route::delete('/{key}', [AppointmentSettingController::class, 'destroy'])->name('destroy');
        Route::post('/reset', [AppointmentSettingController::class, 'resetToDefaults'])->name('reset');
    });
});
