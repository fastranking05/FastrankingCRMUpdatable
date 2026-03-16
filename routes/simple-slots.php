<?php

use App\Http\Controllers\Api\Appointment\SimpleTimeSlotController;
use Illuminate\Support\Facades\Route;

Route::get('/api/simple-slots', [SimpleTimeSlotController::class, 'getAvailableSlots']);
