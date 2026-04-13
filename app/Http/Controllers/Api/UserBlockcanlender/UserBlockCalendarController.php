<?php

namespace App\Http\Controllers\Api\UserBlockcanlender;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\UserBlockCalendar;
use App\Models\User;
use App\Models\TimeSlot;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserBlockCalendarController extends BaseApiController
{
    /**
     * Display a listing of user block calendar entries.
     */
    public function index(Request $request): JsonResponse
    {
        $query = UserBlockCalendar::with([
            'user:id,first_name,last_name,email',
            'timeSlot:id,start_time,end_time',
            'createdBy:id,first_name,last_name,email',
        ]);

        // Filter by user_id if provided
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by date if provided
        if ($request->has('date')) {
            $query->where('date', $request->input('date'));
        }

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('date', [$request->input('start_date'), $request->input('end_date')]);
        }

        // Filter by slot_id if provided
        if ($request->has('slot_id')) {
            $query->where('slot_id', $request->input('slot_id'));
        }

        // Filter by created_by if provided
        if ($request->has('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }

        $blockCalendars = $query->orderBy('date', 'desc')
            ->orderBy('slot_id', 'asc')
            ->get();

        $transformedData = $blockCalendars->map(function ($blockCalendar) {
            return [
                'id' => $blockCalendar->id,
                'user_id' => $blockCalendar->user_id,
                'date' => $blockCalendar->date,
                'slot_id' => $blockCalendar->slot_id,
                'comments' => $blockCalendar->comments,
                'created_by' => $blockCalendar->created_by,
                'created_at' => $blockCalendar->created_at,
                'updated_at' => $blockCalendar->updated_at,
                'user' => $blockCalendar->user ? [
                    'id' => $blockCalendar->user->id,
                    'first_name' => $blockCalendar->user->first_name,
                    'last_name' => $blockCalendar->user->last_name,
                    'email' => $blockCalendar->user->email,
                ] : null,
                'time_slot' => $blockCalendar->timeSlot ? [
                    'id' => $blockCalendar->timeSlot->id,
                    'start_time' => $blockCalendar->timeSlot->start_time,
                    'end_time' => $blockCalendar->timeSlot->end_time,
                ] : null,
                'created_by_user' => $blockCalendar->createdBy ? [
                    'id' => $blockCalendar->createdBy->id,
                    'first_name' => $blockCalendar->createdBy->first_name,
                    'last_name' => $blockCalendar->createdBy->last_name,
                    'email' => $blockCalendar->createdBy->email,
                ] : null,
            ];
        })->toArray();

        return $this->successResponse($transformedData, 'User block calendar entries retrieved successfully');
    }

    /**
     * Store a newly created user block calendar entry.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'slot_id' => 'required|exists:time_slots,id',
            'comments' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // Check if the same user already has a block for the same date and slot
        $existingBlock = UserBlockCalendar::where('user_id', $request->user_id)
            ->where('date', $request->date)
            ->where('slot_id', $request->slot_id)
            ->first();

        if ($existingBlock) {
            return $this->errorResponse('User already has a block calendar entry for this date and slot', 409);
        }

        $blockCalendar = UserBlockCalendar::create([
            'user_id' => $request->user_id,
            'date' => $request->date,
            'slot_id' => $request->slot_id,
            'comments' => $request->comments,
            'created_by' => auth()->id(),
        ]);

        $blockCalendar->load([
            'user:id,first_name,last_name,email',
            'timeSlot:id,start_time,end_time',
            'createdBy:id,first_name,last_name,email',
        ]);

        return $this->successResponse($blockCalendar, 'User block calendar entry created successfully', 201);
    }

    /**
     * Display the specified user block calendar entry.
     */
    public function show($id): JsonResponse
    {
        $blockCalendar = UserBlockCalendar::with([
            'user:id,first_name,last_name,email',
            'timeSlot:id,start_time,end_time',
            'createdBy:id,first_name,last_name,email',
        ])->find($id);

        if (!$blockCalendar) {
            return $this->errorResponse('User block calendar entry not found', 404);
        }

        $transformedData = [
            'id' => $blockCalendar->id,
            'user_id' => $blockCalendar->user_id,
            'date' => $blockCalendar->date,
            'slot_id' => $blockCalendar->slot_id,
            'comments' => $blockCalendar->comments,
            'created_by' => $blockCalendar->created_by,
            'created_at' => $blockCalendar->created_at,
            'updated_at' => $blockCalendar->updated_at,
            'user' => $blockCalendar->user ? [
                'id' => $blockCalendar->user->id,
                'first_name' => $blockCalendar->user->first_name,
                'last_name' => $blockCalendar->user->last_name,
                'email' => $blockCalendar->user->email,
            ] : null,
            'time_slot' => $blockCalendar->timeSlot ? [
                'id' => $blockCalendar->timeSlot->id,
                'start_time' => $blockCalendar->timeSlot->start_time,
                'end_time' => $blockCalendar->timeSlot->end_time,
            ] : null,
            'created_by_user' => $blockCalendar->createdBy ? [
                'id' => $blockCalendar->createdBy->id,
                'first_name' => $blockCalendar->createdBy->first_name,
                'last_name' => $blockCalendar->createdBy->last_name,
                'email' => $blockCalendar->createdBy->email,
            ] : null,
        ];

        return $this->successResponse($transformedData, 'User block calendar entry retrieved successfully');
    }

    /**
     * Update the specified user block calendar entry.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $blockCalendar = UserBlockCalendar::find($id);

        if (!$blockCalendar) {
            return $this->errorResponse('User block calendar entry not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'date' => 'sometimes|date',
            'slot_id' => 'sometimes|exists:time_slots,id',
            'comments' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // Check if the same user already has a block for the same date and slot (excluding current record)
        if ($request->has('user_id') && $request->has('date') && $request->has('slot_id')) {
            $existingBlock = UserBlockCalendar::where('user_id', $request->user_id)
                ->where('date', $request->date)
                ->where('slot_id', $request->slot_id)
                ->where('id', '!=', $id)
                ->first();

            if ($existingBlock) {
                return $this->errorResponse('User already has a block calendar entry for this date and slot', 409);
            }
        }

        $blockCalendar->update([
            'user_id' => $request->user_id ?? $blockCalendar->user_id,
            'date' => $request->date ?? $blockCalendar->date,
            'slot_id' => $request->slot_id ?? $blockCalendar->slot_id,
            'comments' => $request->comments ?? $blockCalendar->comments,
        ]);

        $blockCalendar->load([
            'user:id,first_name,last_name,email',
            'timeSlot:id,start_time,end_time',
            'createdBy:id,first_name,last_name,email',
        ]);

        return $this->successResponse($blockCalendar, 'User block calendar entry updated successfully');
    }

    /**
     * Remove the specified user block calendar entry.
     */
    public function destroy($id): JsonResponse
    {
        $blockCalendar = UserBlockCalendar::find($id);

        if (!$blockCalendar) {
            return $this->errorResponse('User block calendar entry not found', 404);
        }

        $blockCalendar->delete();

        return $this->successResponse(null, 'User block calendar entry deleted successfully');
    }

    /**
     * Get available time slots for a specific date.
     * Excludes slots that have appointments with current_status = 'scheduled' or 'rescheduled'
     */
    public function getAvailableTimeSlots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $date = $request->input('date');

        // Get all time slots
        $allTimeSlots = TimeSlot::all();

        // Get appointments with scheduled or rescheduled status on the given date
        $bookedSlotIds = Appointment::where('date', $date)
            ->whereIn('current_status', ['scheduled', 'rescheduled'])
            ->pluck('time_slot_id')
            ->toArray();

        // Filter out booked slots
        $availableTimeSlots = $allTimeSlots->whereNotIn('id', $bookedSlotIds);

        $transformedData = $availableTimeSlots->map(function ($slot) {
            return [
                'id' => $slot->id,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
            ];
        })->values()->toArray();

        return $this->successResponse($transformedData, 'Available time slots retrieved successfully');
    }
}
