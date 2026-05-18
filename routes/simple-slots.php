<?php

use App\Http\Controllers\Api\Appointment\SimpleTimeSlotController;
use Illuminate\Support\Facades\Route;

Route::get('simple-slots', [SimpleTimeSlotController::class, 'getAvailableSlots']);
Route::post('simple-slots/block', [SimpleTimeSlotController::class, 'blockSlot']);
Route::post('simple-slots/release', [SimpleTimeSlotController::class, 'releaseSlot']);
