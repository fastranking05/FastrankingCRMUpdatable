<?php

namespace App\Http\Controllers\Api\Appointment;

use App\Services\AppointmentBookingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SimpleTimeSlotController
{
    protected $appointmentBookingEngine;

    public function __construct(AppointmentBookingEngine $appointmentBookingEngine)
    {
        $this->appointmentBookingEngine = $appointmentBookingEngine;
    }

    public function getAvailableSlots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $availableSlots = $this->appointmentBookingEngine->getAvailableSlots($request->date);
            
            $slots = [];
            foreach ($availableSlots['available_slots'] ?? [] as $slot) {
                if ($slot['available_bookings'] > 0) {
                    $slots[] = [
                        'id' => $slot['id'],
                        'time' => date('g:i A', strtotime($slot['start_time'])),
                        'available' => $slot['available_bookings']
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'date' => $request->date,
                'slots' => $slots
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get slots'
            ], 500);
        }
    }
}
