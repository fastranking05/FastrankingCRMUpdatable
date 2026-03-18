<?php

namespace App\Http\Controllers\Api\Appointment;

use App\Services\AppointmentBookingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimeSlotPickerController
{
    protected $appointmentBookingEngine;

    public function __construct(AppointmentBookingEngine $appointmentBookingEngine)
    {
        $this->appointmentBookingEngine = $appointmentBookingEngine;
    }

    /**
     * Get available time slots for a specific date
     * Simple endpoint for frontend date picker integration
     */
    public function getAvailableSlotsByDate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'department_id' => 'nullable|integer|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $date = $request->date;
            $departmentId = $request->department_id;

            // Get available slots using the booking engine
            $availableSlots = $this->appointmentBookingEngine->getAvailableSlots($date, $departmentId);

            // Format slots for frontend consumption
            $formattedSlots = [];
            foreach ($availableSlots['available_slots'] ?? [] as $slot) {
                $formattedSlots[] = [
                    'id' => $slot['id'],
                    'name' => $slot['name'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'duration_minutes' => $slot['duration_minutes'],
                    'available_bookings' => $slot['available_bookings'],
                    'max_bookings' => $slot['max_bookings'],
                    'is_available' => $slot['available_bookings'] > 0,
                    'display_time' => $this->formatDisplayTime($slot['start_time']),
                    'time_range' => $this->formatTimeRange($slot['start_time'], $slot['end_time']),
                ];
            }

            // Sort by start time
            usort($formattedSlots, function ($a, $b) {
                return strcmp($a['start_time'], $b['start_time']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Time slots retrieved successfully',
                'data' => [
                    'date' => $date,
                    'department_id' => $departmentId,
                    'total_slots' => count($formattedSlots),
                    'available_slots' => $formattedSlots,
                    'has_available_slots' => count($formattedSlots) > 0,
                    'message' => count($formattedSlots) > 0 
                        ? 'Available time slots found' 
                        : 'No available time slots for selected date'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve time slots',
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG', false) ? $e->getTrace() : null
            ], 500);
        }
    }

    /**
     * Get time slots for a date range (useful for calendar views)
     */
    public function getSlotsForDateRange(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department_id' => 'nullable|integer|exists:departments,id',
            'days' => 'nullable|integer|min:1|max:31', // Limit to 31 days for performance
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $startDate = $request->start_date;
            $endDate = $request->end_date;
            $departmentId = $request->department_id;
            $days = $request->get('days', 7); // Default to 7 days

            // Limit date range for performance
            $maxEndDate = date('Y-m-d', strtotime($startDate . ' +' . $days . ' days'));
            if ($endDate > $maxEndDate) {
                $endDate = $maxEndDate;
            }

            $dateRangeSlots = [];
            $current = new \DateTime($startDate);
            $end = new \DateTime($endDate);

            while ($current <= $end) {
                $dateStr = $current->format('Y-m-d');
                $daySlots = $this->appointmentBookingEngine->getAvailableSlots($dateStr, $departmentId);

                $formattedDaySlots = [];
                foreach ($daySlots['available_slots'] ?? [] as $slot) {
                    $formattedDaySlots[] = [
                        'id' => $slot['id'],
                        'name' => $slot['name'],
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'display_time' => $this->formatDisplayTime($slot['start_time']),
                        'time_range' => $this->formatTimeRange($slot['start_time'], $slot['end_time']),
                        'available' => $slot['available_bookings'] > 0,
                    ];
                }

                $dateRangeSlots[] = [
                    'date' => $dateStr,
                    'day_name' => $current->format('l'),
                    'day_number' => $current->format('d'),
                    'month' => $current->format('F'),
                    'is_today' => $dateStr === date('Y-m-d'),
                    'is_weekend' => in_array($current->format('N'), ['6', '7']), // Saturday, Sunday
                    'total_slots' => count($formattedDaySlots),
                    'available_slots' => $formattedDaySlots,
                    'has_availability' => count($formattedDaySlots) > 0,
                ];

                $current->modify('+1 day');
            }

            return response()->json([
                'success' => true,
                'message' => 'Date range time slots retrieved successfully',
                'data' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'department_id' => $departmentId,
                    'total_days' => count($dateRangeSlots),
                    'days_with_availability' => count(array_filter($dateRangeSlots, fn($day) => $day['has_availability'])),
                    'slots' => $dateRangeSlots,
                    'summary' => [
                        'total_slots_available' => array_sum(array_map(fn($day) => count($day['available_slots']), $dateRangeSlots)),
                        'best_day' => $this->findBestDay($dateRangeSlots),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve date range slots',
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG', false) ? $e->getTrace() : null
            ], 500);
        }
    }

    /**
     * Get quick slot suggestions for next available dates
     */
    public function getNextAvailableSlots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'nullable|integer|min:1|max:14', // Next 14 days
            'department_id' => 'nullable|integer|exists:departments,id',
            'preferred_time' => 'nullable|string', // Morning, Afternoon, Evening
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $days = $request->get('days', 7);
            $departmentId = $request->department_id;
            $preferredTime = $request->preferred_time;

            $nextSlots = [];
            $current = new \DateTime();

            for ($i = 0; $i < $days; $i++) {
                $dateStr = $current->format('Y-m-d');
                $daySlots = $this->appointmentBookingEngine->getAvailableSlots($dateStr, $departmentId);

                $availableSlots = [];
                foreach ($daySlots['available_slots'] ?? [] as $slot) {
                    if ($slot['available_bookings'] > 0) {
                        $availableSlots[] = [
                            'id' => $slot['id'],
                            'name' => $slot['name'],
                            'start_time' => $slot['start_time'],
                            'display_time' => $this->formatDisplayTime($slot['start_time']),
                            'time_range' => $this->formatTimeRange($slot['start_time'], $slot['end_time']),
                            'available_bookings' => $slot['available_bookings'],
                        ];
                    }
                }

                if (!empty($availableSlots)) {
                    $nextSlots[] = [
                        'date' => $dateStr,
                        'day_name' => $current->format('l'),
                        'relative_day' => $this->getRelativeDay($current),
                        'slots' => $availableSlots,
                        'best_slot' => $this->findBestSlot($availableSlots, $preferredTime),
                    ];
                }

                $current->modify('+1 day');
            }

            return response()->json([
                'success' => true,
                'message' => 'Next available slots retrieved successfully',
                'data' => [
                    'days_checked' => $days,
                    'department_id' => $departmentId,
                    'total_dates_with_slots' => count($nextSlots),
                    'next_available_dates' => $nextSlots,
                    'first_available' => !empty($nextSlots) ? $nextSlots[0] : null,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve next available slots',
                'error' => $e->getMessage(),
                'trace' => env('APP_DEBUG', false) ? $e->getTrace() : null
            ], 500);
        }
    }

    /**
     * Format time for display (12-hour format)
     */
    private function formatDisplayTime(string $time): string
    {
        $dateTime = \DateTime::createFromFormat('H:i:s', $time);
        return $dateTime->format('g:i A');
    }

    /**
     * Format time range for display
     */
    private function formatTimeRange(string $startTime, string $endTime): string
    {
        $start = $this->formatDisplayTime($startTime);
        $end = $this->formatDisplayTime($endTime);
        return $start . ' - ' . $end;
    }

    /**
     * Get relative day description
     */
    private function getRelativeDay(\DateTime $date): string
    {
        $today = new \DateTime();
        $diff = $today->diff($date);

        if ($diff->days === 0) {
            return 'Today';
        } elseif ($diff->days === 1) {
            return 'Tomorrow';
        } elseif ($diff->days <= 7) {
            return $date->format('l');
        } else {
            return $date->format('M j');
        }
    }

    /**
     * Find best day with most availability
     */
    private function findBestDay(array $dateRangeSlots): ?array
    {
        $bestDay = null;
        $maxSlots = 0;

        foreach ($dateRangeSlots as $day) {
            $slotCount = count($day['available_slots']);
            if ($slotCount > $maxSlots) {
                $maxSlots = $slotCount;
                $bestDay = $day;
            }
        }

        return $bestDay;
    }

    /**
     * Find best slot based on preferred time
     */
    private function findBestSlot(array $slots, ?string $preferredTime): ?array
    {
        if (empty($slots)) {
            return null;
        }

        // If no preference, return first available
        if (!$preferredTime) {
            return $slots[0];
        }

        // Try to match preferred time
        $preferredTime = strtolower($preferredTime);
        $morningSlots = [];
        $afternoonSlots = [];
        $eveningSlots = [];

        foreach ($slots as $slot) {
            $hour = (int) explode(':', $slot['start_time'])[0];
            
            if ($hour < 12) {
                $morningSlots[] = $slot;
            } elseif ($hour < 17) {
                $afternoonSlots[] = $slot;
            } else {
                $eveningSlots[] = $slot;
            }
        }

        switch ($preferredTime) {
            case 'morning':
                return !empty($morningSlots) ? $morningSlots[0] : $slots[0];
            case 'afternoon':
                return !empty($afternoonSlots) ? $afternoonSlots[0] : $slots[0];
            case 'evening':
                return !empty($eveningSlots) ? $eveningSlots[0] : $slots[0];
            default:
                return $slots[0];
        }
    }
}
