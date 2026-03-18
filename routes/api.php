<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Appointment\SimpleTimeSlotController;
use App\Http\Controllers\Api\Appointment\TimeSlotPickerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth Routes (Public + Protected)
require __DIR__ . '/api/auth.php';

// Admin Module Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/departments.php';
    require __DIR__ . '/api/admin/modules.php';
    require __DIR__ . '/api/admin/roles.php';
    require __DIR__ . '/api/admin/teams.php';
    require __DIR__ . '/api/admin/users.php';
});

// Follow-Up Module Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/followup/followup.php';
    // Individual routes (optional - can be removed if not needed)
    require __DIR__ . '/api/admin/followup/businesses.php';
    require __DIR__ . '/api/admin/followup/auth-persons.php';
    require __DIR__ . '/api/admin/followup/details.php';
});

// Appointment Module Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/appointment/appointments.php';
    require __DIR__ . '/api/admin/appointment/time-slots.php';
    require __DIR__ . '/api/admin/appointment/settings.php';
});

// Public Time Slot Routes (No Auth Required)
Route::prefix('time-slots')->name('public.time-slots.')->group(function () {
    Route::get('/available', [TimeSlotPickerController::class, 'getAvailableSlotsByDate'])->name('available');
});

// Simple Time Slot API
Route::get('/simple-slots', [SimpleTimeSlotController::class, 'getAvailableSlots']);

// Public Time Slot Picker Routes (No Auth Required)
require __DIR__ . '/api/admin/appointment/time-slots-picker.php';
