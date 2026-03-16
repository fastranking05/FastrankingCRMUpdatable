<?php

namespace App\Http\Controllers\Api\Appointment;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\AppointmentSetting;
use App\Models\TimeSlot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TimeSlotController extends BaseApiController
{
    /**
     * List all time slots
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = TimeSlot::query();

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Filter by department
            if ($request->has('department_id')) {
                $query->where(function ($q) use ($request) {
                    $q->whereNull('department_ids')
                      ->orWhereJsonContains('department_ids', $request->department_id);
                });
            }

            $timeSlots = $query->orderBy('start_time')->get();

            return $this->successResponse($timeSlots, 'Time slots retrieved successfully');
        }, 'Time slots list retrieval');
    }

    /**
     * Create a new time slot
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'duration_minutes' => 'required|integer|min:1',
            'is_active' => 'boolean',
            'max_concurrent_bookings' => 'required|integer|min:1',
            'description' => 'nullable|string',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $timeSlot = TimeSlot::create($request->all());

            return $this->successResponse($timeSlot, 'Time slot created successfully', 201);
        }, 'Time slot creation');
    }

    /**
     * Show time slot details
     */
    public function show(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $timeSlot = TimeSlot::with([
                'appointments' => function ($query) {
                    $query->whereIn('current_status', ['Booked', 'Confirmed', 'In Progress'])
                          ->orderBy('date', 'desc')
                          ->limit(10);
                }
            ])->find($id);

            if (!$timeSlot) {
                return $this->errorResponse('Time slot not found', 404);
            }

            return $this->successResponse($timeSlot, 'Time slot retrieved successfully');
        }, 'Time slot retrieval', ['time_slot_id' => $id]);
    }

    /**
     * Update time slot
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $timeSlot = TimeSlot::find($id);

        if (!$timeSlot) {
            return $this->errorResponse('Time slot not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:100',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'duration_minutes' => 'sometimes|required|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'max_concurrent_bookings' => 'sometimes|required|integer|min:1',
            'description' => 'nullable|string',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $timeSlot) {
            $timeSlot->update($request->all());

            return $this->successResponse($timeSlot, 'Time slot updated successfully');
        }, 'Time slot update', ['time_slot_id' => $timeSlot->id]);
    }

    /**
     * Delete time slot
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $timeSlot = TimeSlot::find($id);

            if (!$timeSlot) {
                return $this->errorResponse('Time slot not found', 404);
            }

            // Check if slot has future appointments
            $futureAppointments = $timeSlot->appointments()
                ->where('date', '>=', now()->toDateString())
                ->whereIn('current_status', ['Booked', 'Confirmed', 'In Progress'])
                ->count();

            if ($futureAppointments > 0) {
                return $this->errorResponse('Cannot delete time slot with future appointments', 400);
            }

            $timeSlot->delete();

            return $this->successResponse(null, 'Time slot deleted successfully');
        }, 'Time slot deletion', ['time_slot_id' => $id]);
    }

    /**
     * Bulk create time slots
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'slots' => 'required|array|min:1',
            'slots.*.name' => 'required|string|max:100',
            'slots.*.start_time' => 'required|date_format:H:i',
            'slots.*.end_time' => 'required|date_format:H:i|after:slots.*.start_time',
            'slots.*.duration_minutes' => 'required|integer|min:1',
            'slots.*.max_concurrent_bookings' => 'required|integer|min:1',
            'slots.*.description' => 'nullable|string',
            'slots.*.department_ids' => 'nullable|array',
            'slots.*.department_ids.*' => 'exists:departments,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $createdSlots = [];
            
            foreach ($request->slots as $slotData) {
                $slotData['is_active'] = $slotData['is_active'] ?? true;
                $slot = TimeSlot::create($slotData);
                $createdSlots[] = $slot;
            }

            return $this->successResponse($createdSlots, 'Time slots created successfully', 201);
        }, 'Bulk time slots creation');
    }

    /**
     * Get time slot statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $dateFrom = $request->date_from ?? now()->toDateString();
            $dateTo = $request->date_to ?? now()->addDays(30)->toDateString();

            $stats = [
                'total_slots' => 0,
                'active_slots' => 0,
                'total_capacity' => 0,
                'utilization' => 0,
                'top_performing_slots' => [],
                'least_performing_slots' => [],
            ];

            $timeSlots = TimeSlot::all();
            $stats['total_slots'] = $timeSlots->count();
            $stats['active_slots'] = $timeSlots->where('is_active', true)->count();

            foreach ($timeSlots as $slot) {
                $stats['total_capacity'] += $slot->max_concurrent_bookings;
                
                $bookings = $slot->appointments()
                    ->whereBetween('date', [$dateFrom, $dateTo])
                    ->whereIn('current_status', ['Booked', 'Confirmed', 'In Progress', 'Conducted'])
                    ->count();

                $slot->stats = [
                    'total_bookings' => $bookings,
                    'utilization_rate' => $slot->max_concurrent_bookings > 0 
                        ? ($bookings / ($slot->max_concurrent_bookings * 30)) * 100 // Assuming 30 days period
                        : 0,
                ];
            }

            // Calculate overall utilization
            $totalBookings = $timeSlots->sum(function ($slot) use ($dateFrom, $dateTo) {
                return $slot->appointments()
                    ->whereBetween('date', [$dateFrom, $dateTo])
                    ->whereIn('current_status', ['Booked', 'Confirmed', 'In Progress', 'Conducted'])
                    ->count();
            });

            $totalCapacity = $stats['total_capacity'] * 30; // 30 days period
            $stats['utilization'] = $totalCapacity > 0 ? ($totalBookings / $totalCapacity) * 100 : 0;

            // Sort by performance
            $sortedSlots = $timeSlots->sortByDesc('stats.utilization_rate');
            $stats['top_performing_slots'] = $sortedSlots->take(5)->values();
            $stats['least_performing_slots'] = $sortedSlots->reverse()->take(5)->values();

            return $this->successResponse($stats, 'Time slot statistics retrieved successfully');
        }, 'Time slot statistics retrieval');
    }
}
