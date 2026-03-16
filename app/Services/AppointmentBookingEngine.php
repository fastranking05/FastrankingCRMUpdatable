<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentSetting;
use App\Models\AppointmentTemporaryBooking;
use App\Models\FollowupBusiness;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentBookingEngine
{
    /**
     * Get available time slots for a specific date and department
     */
    public function getAvailableSlots(string $date, ?int $departmentId = null): array
    {
        // Validate date
        if (!$this->isValidBookingDate($date)) {
            return [];
        }

        // Clean up expired temporary bookings
        AppointmentTemporaryBooking::cleanupExpired();

        return TimeSlot::getAvailableSlots($date, $departmentId);
    }

    /**
     * Hold a time slot temporarily (15 minutes)
     */
    public function holdTimeSlot(string $date, int $timeSlotId, int $userId, string $sessionId): array
    {
        // Validate date and slot
        if (!$this->isValidBookingDate($date)) {
            return ['success' => false, 'message' => 'Invalid booking date'];
        }

        $timeSlot = TimeSlot::find($timeSlotId);
        if (!$timeSlot || !$timeSlot->is_active) {
            return ['success' => false, 'message' => 'Time slot not available'];
        }

        // Check if slot is available
        if (!$timeSlot->isAvailableForDate($date)) {
            return ['success' => false, 'message' => 'Time slot is already fully booked'];
        }

        // Create temporary booking
        $tempBooking = AppointmentTemporaryBooking::holdSlot($date, $timeSlotId, $userId, $sessionId);

        return [
            'success' => true,
            'message' => 'Time slot held successfully',
            'expires_at' => $tempBooking->expires_at->toISOString(),
            'booking_id' => $tempBooking->id,
        ];
    }

    /**
     * Confirm appointment (convert temporary booking to actual appointment)
     */
    public function confirmAppointment(array $appointmentData, string $sessionId): array
    {
        try {
            DB::beginTransaction();

            $date = $appointmentData['date'];
            $timeSlotId = $appointmentData['time_slot_id'];
            $userId = $appointmentData['created_by'];

            // Find temporary booking
            $tempBooking = AppointmentTemporaryBooking::where('date', $date)
                ->where('time_slot_id', $timeSlotId)
                ->where('user_id', $userId)
                ->where('session_id', $sessionId)
                ->where('expires_at', '>', now())
                ->first();

            if (!$tempBooking) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Temporary booking not found or expired'];
            }

            // Check if business exists or create new one
            $business = $this->findOrCreateBusiness($appointmentData);

            // Create appointment
            $appointmentData['followup_business_id'] = $business->id;
            $appointment = $tempBooking->confirmAppointment($appointmentData);

            // Update business status if needed
            if (isset($appointmentData['status'])) {
                $this->updateBusinessAppointmentStatus($business, $appointmentData['status']);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Appointment confirmed successfully',
                'appointment' => $appointment->load(['followupBusiness', 'timeSlot', 'creator']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Appointment confirmation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to confirm appointment'];
        }
    }

    /**
     * Create direct appointment (without temporary booking)
     */
    public function createDirectAppointment(array $appointmentData): array
    {
        try {
            DB::beginTransaction();

            $date = $appointmentData['date'];
            $timeSlotId = $appointmentData['time_slot_id'];

            // Validate availability
            $timeSlot = TimeSlot::find($timeSlotId);
            if (!$timeSlot || !$timeSlot->isAvailableForDate($date)) {
                DB::rollBack();
                return ['success' => false, 'message' => 'Time slot not available'];
            }

            // Find or create business
            $business = $this->findOrCreateBusiness($appointmentData);

            // Create appointment
            $appointmentData['followup_business_id'] = $business->id;
            $appointment = Appointment::create($appointmentData);

            // Update business status
            $this->updateBusinessAppointmentStatus($business, $appointmentData['status'] ?? 'Appointment Booked');

            DB::commit();

            return [
                'success' => true,
                'message' => 'Appointment created successfully',
                'appointment' => $appointment->load(['followupBusiness', 'timeSlot', 'creator']),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Direct appointment creation failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create appointment'];
        }
    }

    /**
     * Release temporary booking
     */
    public function releaseTimeSlot(string $date, int $timeSlotId, string $sessionId): bool
    {
        $tempBooking = AppointmentTemporaryBooking::where('date', $date)
            ->where('time_slot_id', $timeSlotId)
            ->where('session_id', $sessionId)
            ->first();

        if ($tempBooking) {
            $tempBooking->release();
            return true;
        }

        return false;
    }

    /**
     * Get appointment statistics for a date
     */
    public function getAppointmentStats(string $date, ?int $departmentId = null): array
    {
        $stats = [
            'total_slots' => 0,
            'available_slots' => 0,
            'booked_slots' => 0,
            'held_slots' => 0,
            'slots' => [],
        ];

        $timeSlots = TimeSlot::where('is_active', true)
            ->when($departmentId, function ($query, $deptId) {
                $query->where(function ($q) use ($deptId) {
                    $q->whereNull('department_ids')
                      ->orWhereJsonContains('department_ids', $deptId);
                });
            })
            ->orderBy('start_time')
            ->get();

        foreach ($timeSlots as $slot) {
            $bookings = $slot->appointments()->where('date', $date)
                ->whereIn('current_status', ['Booked', 'Confirmed', 'In Progress'])
                ->count();

            $held = $slot->temporaryBookings()->where('date', $date)
                ->where('expires_at', '>', now())
                ->count();

            $available = $slot->max_concurrent_bookings - ($bookings + $held);
            $isAvailable = $available > 0;

            $stats['total_slots'] += $slot->max_concurrent_bookings;
            $stats['available_slots'] += $available;
            $stats['booked_slots'] += $bookings;
            $stats['held_slots'] += $held;

            $stats['slots'][] = [
                'id' => $slot->id,
                'name' => $slot->name,
                'time' => $slot->start_time->format('H:i') . ' - ' . $slot->end_time->format('H:i'),
                'max_bookings' => $slot->max_concurrent_bookings,
                'booked' => $bookings,
                'held' => $held,
                'available' => $available,
                'is_available' => $isAvailable,
            ];
        }

        return $stats;
    }

    /**
     * Check if date is valid for booking
     */
    private function isValidBookingDate(string $date): bool
    {
        try {
            $bookingDate = \Carbon\Carbon::parse($date);
            $today = \Carbon\Carbon::today();
            
            // Check if date is in the past
            if ($bookingDate < $today) {
                return false;
            }

            // Check if date is too far in advance
            $maxAdvanceDays = AppointmentSetting::getValue('booking_advance_days', 30);
            if ($bookingDate > $today->copy()->addDays($maxAdvanceDays)) {
                return false;
            }

            // Check if it's a working day
            $workingDays = AppointmentSetting::getValue('working_days', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
            if (!in_array($bookingDate->format('l'), $workingDays)) {
                return false;
            }

            return true;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Find or create business for appointment
     */
    private function findOrCreateBusiness(array $appointmentData): FollowupBusiness
    {
        // If followup_business_id is provided, use existing business
        if (isset($appointmentData['followup_business_id'])) {
            return FollowupBusiness::findOrFail($appointmentData['followup_business_id']);
        }

        // If business data is provided, create new business
        if (isset($appointmentData['business'])) {
            $businessData = $appointmentData['business'];
            $businessData['created_by'] = $appointmentData['created_by'];
            
            $business = FollowupBusiness::create($businessData);

            // Create auth persons if provided
            if (isset($appointmentData['auth_persons'])) {
                foreach ($appointmentData['auth_persons'] as $personData) {
                    $personData['created_by'] = $appointmentData['created_by'];
                    $person = FollowupAuthPerson::create($personData);
                    $business->authPersons()->attach($person->id);
                }
            }

            return $business;
        }

        throw new \Exception('Business information is required');
    }

    /**
     * Update business appointment status
     */
    private function updateBusinessAppointmentStatus(FollowupBusiness $business, string $status): void
    {
        // Update the latest follow-up detail to appointment status
        $latestDetail = $business->followupDetails()->latest()->first();
        if ($latestDetail) {
            $latestDetail->update(['status' => $status]);
        }
    }
}
