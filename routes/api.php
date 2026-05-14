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

// Business Management Module Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/business-categories.php';
    require __DIR__ . '/api/admin/business-types.php';
});

// Follow-Up Module Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/followup/followup.php';
    // Individual routes (optional - can be removed if not needed)
    require __DIR__ . '/api/admin/followup/businesses.php';
    require __DIR__ . '/api/admin/followup/auth-persons.php';
    require __DIR__ . '/api/admin/followup/details.php';
});

// Leads Module Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/leads/leads.php';
});

// Email Module Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/email/email.php';
});

// Appointment Module Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/appointment/appointments.php';
    require __DIR__ . '/api/admin/appointment/time-slots.php';
    require __DIR__ . '/api/admin/appointment/settings.php';
});

// Quality Control Module Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/quality.php';
});

// Quality Questions Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/quality-questions.php';
});

// Quality Data Submission Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/quality-data-submission.php';
});

// Quality Audit Routes
Route::group([], function () {
    require __DIR__ . '/api/quality-audit.php';
});

// SEO Audit Routes
Route::group([], function () {
    require __DIR__ . '/api/seo-audit.php';
});

// SEO Questions Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/seo/questions.php';
});

// Consultation Routes
Route::group([], function () {
    require __DIR__ . '/api/consultation.php';
});

// User Assignment Routes
Route::group([], function () {
    require __DIR__ . '/api/user-assignment.php';
});

// Filter System Routes
Route::group([], function () {
    require __DIR__ . '/api/filters.php';
});

// User Block Calendar Routes
Route::group([], function () {
    require __DIR__ . '/api/admin/UserBlockcanlender/user-block-calendar.php';
});

// Public Time Slot Routes (No Auth Required)
Route::prefix('time-slots')->name('public.time-slots.')->group(function () {
    Route::get('/available', [TimeSlotPickerController::class, 'getAvailableSlotsByDate'])->name('available');
});

// Simple Time Slot API
require __DIR__ . '/simple-slots.php';

// Public Time Slot Picker Routes (No Auth Required)
require __DIR__ . '/api/admin/appointment/time-slots-picker.php';
