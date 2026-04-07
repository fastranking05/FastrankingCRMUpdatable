<?php

namespace App\Http\Controllers\Api\Appointment;

use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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
            Log::error('Error getting available slots', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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
            'date' => 'required|date|after_or_equal:today',
            'session_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            Log::info('Block slot request', [
                'date' => $request->date,
                'time_slot_id' => $request->time_slot_id,
                'user_id' => auth()->id(),
                'session_id' => $request->session_id
            ]);

            // Block slot using existing temporary booking system
            $result = $this->holdTimeSlot($request->date, $request->time_slot_id, auth()->id(), $request->session_id);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error blocking slot', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to block slot: ' . $e->getMessage()
            ], 500);
        }
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

        try {
            Log::info('Release slot request', [
                'block_id' => $request->block_id,
                'user_id' => auth()->id()
            ]);

            // Release slot using existing temporary booking system
            $result = $this->confirmAppointment([
                'block_id' => $request->block_id
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error releasing slot', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to release slot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hold a time slot temporarily (15 minutes)
     */
    private function holdTimeSlot(string $date, int $timeSlotId, ?int $userId, string $sessionId): array
    {
        try {
            Log::info('Attempting to hold time slot', [
                'date' => $date,
                'time_slot_id' => $timeSlotId,
                'user_id' => $userId,
                'session_id' => $sessionId
            ]);

            $timeSlot = TimeSlot::find($timeSlotId);
            if (!$timeSlot || !$timeSlot->is_active) {
                return ['success' => false, 'message' => 'Time slot not available'];
            }

            // Check if slot is available for this date
            $currentBookings = $this->getCurrentBookingsCount($timeSlotId, $date);
            $maxConcurrentBookings = TimeSlot::getActiveSalesDepartmentUserCount();
            $isAvailable = ($currentBookings < $maxConcurrentBookings);

            if (!$isAvailable) {
                return ['success' => false, 'message' => 'Time slot is already fully booked'];
            }

            // Create temporary booking with fallback user_id
            $tempBooking = \App\Models\AppointmentTemporaryBooking::create([
                'date' => $date,
                'time_slot_id' => $timeSlotId,
                'user_id' => $userId ?? 1, // Fallback to user ID 1 if not authenticated
                'session_id' => $sessionId,
                'expires_at' => now()->addMinutes(15),
            ]);

            Log::info('Time slot held successfully', [
                'booking_id' => $tempBooking->id,
                'time_slot_id' => $timeSlotId,
                'expires_at' => $tempBooking->expires_at
            ]);

            return [
                'success' => true,
                'message' => 'Time slot held successfully',
                'booking_id' => $tempBooking->id
            ];

        } catch (\Exception $e) {
            Log::error('Error holding time slot', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'date' => $date,
                'time_slot_id' => $timeSlotId,
                'user_id' => $userId,
                'session_id' => $sessionId
            ]);
            
            return ['success' => false, 'message' => 'Failed to hold time slot: ' . $e->getMessage()];
        }
    }

    /**
     * Confirm appointment (convert temporary booking to actual appointment)
     */
    private function confirmAppointment(array $appointmentData): array
    {
        try {
            $blockId = $appointmentData['block_id'];
            
            Log::info('Attempting to release slot', ['block_id' => $blockId]);
            
            // Find temporary booking by block_id
            $tempBooking = \App\Models\AppointmentTemporaryBooking::find($blockId);
            
            if (!$tempBooking) {
                return ['success' => false, 'message' => 'Temporary booking not found or expired'];
            }

            Log::info('Temporary booking found and deleted', [
                'booking_id' => $tempBooking->id,
                'time_slot_id' => $tempBooking->time_slot_id,
                'date' => $tempBooking->date
            ]);

            // Delete temporary booking
            $tempBooking->delete();

            return [
                'success' => true,
                'message' => 'Time slot released successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error releasing time slot', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'block_id' => $blockId
            ]);
            
            return ['success' => false, 'message' => 'Failed to release time slot: ' . $e->getMessage()];
        }
    }

    /**
     * Get current bookings count for a specific slot and date
     */
    private function getCurrentBookingsCount(int $slotId, string $date): int
    {
        try {
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
        } catch (\Exception $e) {
            Log::error('Error getting current bookings count', [
                'slot_id' => $slotId,
                'date' => $date,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
