<?php

use App\Http\Controllers\Api\Appointment\SimpleTimeSlotController;
use Illuminate\Support\Facades\Route;

Route::get('/api/simple-slots', [SimpleTimeSlotController::class, 'getAvailableSlots']);
Route::post('/api/simple-slots/block', [SimpleTimeSlotController::class, 'blockSlot']);
Route::post('/api/simple-slots/release', [SimpleTimeSlotController::class, 'releaseSlot']);
