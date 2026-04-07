<?php

namespace App\Http\Controllers\Api\Appointment;

use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SimpleTimeSlotController
{
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
            // Get active time slots for the date
            $activeSlots = TimeSlot::where('is_active', true)
                ->orderBy('start_time')
                ->get();

            // Get dynamic max concurrent bookings based on active Sales department users
            $maxConcurrentBookings = TimeSlot::getActiveSalesDepartmentUserCount();
            
            $slots = [];
            foreach ($activeSlots as $slot) {
                // Check if slot is blocked by existing temporary bookings
                $isBlockedByTempBooking = $slot->temporaryBookings()
                    ->where('date', $request->date)
                    ->where('expires_at', '>', now())
                    ->exists();

                // Check if slot is available considering blocking
                $currentBookings = $this->getCurrentBookingsCount($slot->id, $request->date);
                $isAvailable = ($currentBookings < $maxConcurrentBookings) && !$isBlockedByTempBooking;

                if ($isAvailable) {
                    $slots[] = [
                        'id' => $slot->id,
                        'name' => $slot->name,
                        'time' => date('g:i A', strtotime($slot->start_time->format('H:i'))),
                        'available' => $maxConcurrentBookings - $currentBookings,
                        'blocked' => false
                    ];
                } else {
                    // Show slot as blocked if not available
                    $slots[] = [
                        'id' => $slot->id,
                        'name' => $slot->name,
                        'time' => date('g:i A', strtotime($slot->start_time->format('H:i'))),
                        'available' => 0,
                        'blocked' => true
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

    public function blockSlot(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'time_slot_id' => 'required|exists:time_slots,id',
            'session_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 422);
        }

        // Block slot using existing temporary booking system
        $result = $this->holdTimeSlot($request->date, $request->time_slot_id, auth()->id(), $request->session_id);

        return response()->json($result);
    }

    public function releaseSlot(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'block_id' => 'required|exists:appointment_temporary_bookings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 422);
        }

        // Release slot using existing temporary booking system
        $result = $this->confirmAppointment([
            'date' => $request->date,
            'time_slot_id' => $request->time_slot_id,
            'created_by' => auth()->id(),
            'session_id' => $request->session_id
        ]);

        return response()->json($result);
    }

    /**
     * Hold a time slot temporarily (15 minutes)
     */
    private function holdTimeSlot(string $date, int $timeSlotId, int $userId, string $sessionId): array
    {
        try {
            $timeSlot = TimeSlot::find($timeSlotId);
            if (!$timeSlot || !$timeSlot->is_active) {
                return ['success' => false, 'message' => 'Time slot not available'];
            }

            // Check if slot is available for this date
            if (!$timeSlot->isAvailableForDate($date)) {
                return ['success' => false, 'message' => 'Time slot is already fully booked'];
            }

            // Create temporary booking
            $tempBooking = \App\Models\AppointmentTemporaryBooking::create([
                'date' => $date,
                'time_slot_id' => $timeSlotId,
                'user_id' => $userId,
                'session_id' => $sessionId,
                'expires_at' => now()->addMinutes(15),
            ]);

            return [
                'success' => true,
                'message' => 'Time slot held successfully',
                'booking_id' => $tempBooking->id
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to hold time slot'];
        }
    }

    /**
     * Confirm appointment (convert temporary booking to actual appointment)
     */
    private function confirmAppointment(array $appointmentData): array
    {
        try {
            $date = $appointmentData['date'];
            $timeSlotId = $appointmentData['time_slot_id'];
            $userId = $appointmentData['created_by'];
            $sessionId = $appointmentData['session_id'];

            // Find temporary booking
            $tempBooking = \App\Models\AppointmentTemporaryBooking::where('date', $date)
                ->where('time_slot_id', $timeSlotId)
                ->where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->where('expires_at', '>', now())
                ->first();

            if (!$tempBooking) {
                return ['success' => false, 'message' => 'Temporary booking not found or expired'];
            }

            // Delete temporary booking
            $tempBooking->delete();

            return [
                'success' => true,
                'message' => 'Time slot released successfully'
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to release time slot'];
        }
    }

    /**
     * Get current bookings count for a specific slot and date
     */
    private function getCurrentBookingsCount(int $slotId, string $date): int
    {
        $slot = TimeSlot::find($slotId);
        if (!$slot) {
            return 0;
        }

        $appointments = $slot->appointments()
            ->where('date', $date)
            ->whereIn('current_status', ['Appointment Booked', 'Confirmed', 'In Progress'])
            ->count();

        $tempBookings = $slot->temporaryBookings()
            ->where('date', $date)
            ->where('expires_at', '>', now())
            ->count();

        return $appointments + $tempBookings;
    }
}
