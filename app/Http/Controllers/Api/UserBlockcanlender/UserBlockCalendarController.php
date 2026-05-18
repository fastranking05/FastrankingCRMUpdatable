<?php

namespace App\Http\Controllers\Api\UserBlockcanlender;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\UserBlockCalendar;
use App\Models\User;
use App\Models\TimeSlot;
use App\Models\Appointment;
use App\Models\Consultation;
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

    /**
     * Get schedule details for a specific date.
     * Returns appointments, consultations, and user block calendar entries for the date.
     * Role-based access control:
     * - Executive: Only sees their own data
     * - Admin/Director: Sees all data
     * - Manager: Sees their team and department data
     */
    public function getScheduleDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $date = $request->input('date');

        // Get authenticated user
        $currentUser = auth()->user();
        if (!$currentUser) {
            return $this->errorResponse('User not authenticated', 401);
        }

        // Get user roles
        $userRoles = $currentUser->roles()->pluck('name')->toArray();
        $currentUser->load(['teams', 'departments']);

        // Determine access level (case-insensitive)
        $isAdminOrDirector = in_array('Admin', $userRoles, true) || in_array('Director', $userRoles, true) ||
                            in_array('admin', $userRoles, true) || in_array('director', $userRoles, true);
        $isExecutive = in_array('Executive', $userRoles, true) || in_array('executive', $userRoles, true);
        $isManager = in_array('Manager', $userRoles, true) || in_array('manager', $userRoles, true);

        // Build user IDs filter based on role
        $allowedUserIds = [];
        if ($isExecutive) {
            // Executive: Only see their own data
            $allowedUserIds = [$currentUser->id];
        } elseif ($isManager) {
            // Manager: See their team and department data
            $teamMemberIds = $currentUser->teams()->with('users')->get()->pluck('users')->flatten()->pluck('id')->toArray();
            $departmentMemberIds = $currentUser->departments()->with('users')->get()->pluck('users')->flatten()->pluck('id')->toArray();
            $allowedUserIds = array_unique(array_merge($teamMemberIds, $departmentMemberIds, [$currentUser->id]));
        }
        // Admin/Director: No filter (see all data)

        // Get appointments for the date with role-based filtering
        $appointmentsQuery = Appointment::with([
            'followupBusiness:id,name,phone,email',
            'timeSlot:id,start_time,end_time',
            'creator:id,first_name,last_name,email,username'
        ])
        ->where('date', $date);

        // Apply role-based filter for appointments
        if ($isExecutive || $isManager) {
            $appointmentsQuery->where(function($query) use ($allowedUserIds, $date) {
                // Filter by created_by
                $query->whereIn('created_by', $allowedUserIds);
                // Also include appointments where the LATEST consultation is assigned to the user
                $latestConsultationIds = Consultation::select(DB::raw('MAX(id) as id'))
                    ->whereHas('appointment', function($q) use ($date) {
                        $q->where('date', $date);
                    })
                    ->groupBy('appointment_id')
                    ->pluck('id');

                $appointmentIdsWithLatestConsultation = Consultation::whereIn('id', $latestConsultationIds)
                    ->where(function($q) use ($allowedUserIds) {
                        $q->whereIn('assigned_user', $allowedUserIds)
                          ->orWhereIn('closer', $allowedUserIds);
                    })
                    ->pluck('appointment_id')
                    ->toArray();

                if (!empty($appointmentIdsWithLatestConsultation)) {
                    $query->orWhereIn('id', $appointmentIdsWithLatestConsultation);
                }
            });
        }

        $appointments = $appointmentsQuery->orderBy('time_slot_id')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'followup_business_id' => $appointment->followup_business_id,
                    'business_name' => $appointment->followupBusiness->name ?? null,
                    'contact_person' => $appointment->followupBusiness->email ?? null,
                    'contact_number' => $appointment->followupBusiness->phone ?? null,
                    'date' => $appointment->date,
                    'time_slot' => $appointment->timeSlot ? [
                        'id' => $appointment->timeSlot->id,
                        'start_time' => $appointment->timeSlot->start_time,
                        'end_time' => $appointment->timeSlot->end_time,
                    ] : null,
                    'current_status' => $appointment->current_status,
                    'created_by' => $appointment->creator ? [
                        'id' => $appointment->creator->id,
                        'first_name' => $appointment->creator->first_name,
                        'last_name' => $appointment->creator->last_name,
                        'email' => $appointment->creator->email,
                        'username' => $appointment->creator->username,
                    ] : null,
                ];
            });

        // Get consultations for the date with role-based filtering
        $consultationsQuery = Consultation::with([
            'appointment' => function($query) {
                $query->with([
                    'followupBusiness:id,name,phone,email',
                    'timeSlot:id,start_time,end_time'
                ]);
            },
            'closer:id,first_name,last_name,username',
            'assignedUser:id,first_name,last_name,username'
        ])
        ->whereHas('appointment', function($query) use ($date) {
            $query->where('date', $date);
        });

        // Apply role-based filter for consultations (filter by assigned_user or closer)
        if ($isExecutive || $isManager) {
            $consultationsQuery->where(function($query) use ($allowedUserIds) {
                $query->whereIn('assigned_user', $allowedUserIds)
                      ->orWhereIn('closer', $allowedUserIds);
            });
        }

        // Debug: Log the query and conditions
        \Log::info('Consultation Query Debug', [
            'current_user_id' => $currentUser->id,
            'user_roles' => $userRoles,
            'is_admin_or_director' => $isAdminOrDirector,
            'is_executive' => $isExecutive,
            'is_manager' => $isManager,
            'allowed_user_ids' => $allowedUserIds,
            'date' => $date,
        ]);

        // Get only the latest consultation per appointment using subquery
        $latestConsultationIds = Consultation::select(DB::raw('MAX(id) as id'))
            ->whereHas('appointment', function($query) use ($date) {
                $query->where('date', $date);
            })
            ->groupBy('appointment_id')
            ->pluck('id');

        // Apply role-based filter to the latest consultations
        if ($isExecutive || $isManager) {
            $consultationsQuery->where(function($query) use ($allowedUserIds, $latestConsultationIds) {
                $query->whereIn('id', $latestConsultationIds)
                      ->where(function($q) use ($allowedUserIds) {
                          $q->whereIn('assigned_user', $allowedUserIds)
                            ->orWhereIn('closer', $allowedUserIds);
                      });
            });
        } else {
            $consultationsQuery->whereIn('id', $latestConsultationIds);
        }

        $consultations = $consultationsQuery->orderBy('id', 'desc')
            ->get()
            ->map(function ($consultation) {
                return [
                    'id' => $consultation->id,
                    'appointment_id' => $consultation->appointment_id,
                    'business_name' => $consultation->appointment->followupBusiness->name ?? null,
                    'contact_person' => $consultation->appointment->followupBusiness->email ?? null,
                    'contact_number' => $consultation->appointment->followupBusiness->phone ?? null,
                    'date' => $consultation->appointment->date,
                    'time_slot' => $consultation->appointment->timeSlot ? [
                        'id' => $consultation->appointment->timeSlot->id,
                        'start_time' => $consultation->appointment->timeSlot->start_time,
                        'end_time' => $consultation->appointment->timeSlot->end_time,
                    ] : null,
                    'status' => $consultation->status,
                    'custom_status' => $consultation->custom_status,
                    'assigned_user' => $consultation->assignedUser ? [
                        'id' => $consultation->assignedUser->id,
                        'first_name' => $consultation->assignedUser->first_name,
                        'last_name' => $consultation->assignedUser->last_name,
                        'username' => $consultation->assignedUser->username,
                    ] : null,
                    'closer' => $consultation->closer ? [
                        'id' => $consultation->closer->id,
                        'first_name' => $consultation->closer->first_name,
                        'last_name' => $consultation->closer->last_name,
                        'username' => $consultation->closer->username,
                    ] : null,
                ];
            });

        // Get user block calendar entries for the date with role-based filtering
        $userBlockCalendarsQuery = UserBlockCalendar::with([
            'user:id,first_name,last_name,email',
            'timeSlot:id,start_time,end_time',
            'createdBy:id,first_name,last_name,email'
        ])
        ->where('date', $date);

        // Apply role-based filter for user block calendar entries
        if ($isExecutive || $isManager) {
            $userBlockCalendarsQuery->where(function($query) use ($allowedUserIds) {
                $query->whereIn('user_id', $allowedUserIds)
                      ->orWhereIn('created_by', $allowedUserIds);
            });
        }

        $userBlockCalendars = $userBlockCalendarsQuery->orderBy('slot_id')
            ->get()
            ->map(function ($blockCalendar) {
                return [
                    'id' => $blockCalendar->id,
                    'user_id' => $blockCalendar->user_id,
                    'date' => $blockCalendar->date,
                    'slot_id' => $blockCalendar->slot_id,
                    'comments' => $blockCalendar->comments,
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
                    'created_by' => $blockCalendar->createdBy ? [
                        'id' => $blockCalendar->createdBy->id,
                        'first_name' => $blockCalendar->createdBy->first_name,
                        'last_name' => $blockCalendar->createdBy->last_name,
                        'email' => $blockCalendar->createdBy->email,
                    ] : null,
                ];
            });

        // Get scheduled and rescheduled appointments for the date with role-based filtering
        $scheduledAppointmentsQuery = Appointment::with([
            'followupBusiness:id,name,phone,email',
            'timeSlot:id,start_time,end_time',
            'creator:id,first_name,last_name,email,username'
        ])
        ->where('date', $date)
        ->whereIn('current_status', ['scheduled', 'rescheduled']);

        // Apply role-based filter for scheduled/rescheduled appointments
        if ($isExecutive || $isManager) {
            $scheduledAppointmentsQuery->where(function($query) use ($allowedUserIds, $date) {
                // Filter by created_by
                $query->whereIn('created_by', $allowedUserIds);
                // Also include appointments where the LATEST consultation is assigned to the user
                $latestConsultationIds = Consultation::select(DB::raw('MAX(id) as id'))
                    ->whereHas('appointment', function($q) use ($date) {
                        $q->where('date', $date);
                    })
                    ->groupBy('appointment_id')
                    ->pluck('id');

                $appointmentIdsWithLatestConsultation = Consultation::whereIn('id', $latestConsultationIds)
                    ->where(function($q) use ($allowedUserIds) {
                        $q->whereIn('assigned_user', $allowedUserIds)
                          ->orWhereIn('closer', $allowedUserIds);
                    })
                    ->pluck('appointment_id')
                    ->toArray();

                if (!empty($appointmentIdsWithLatestConsultation)) {
                    $query->orWhereIn('id', $appointmentIdsWithLatestConsultation);
                }
            });
        }

        $scheduledAppointments = $scheduledAppointmentsQuery->orderBy('time_slot_id')
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'followup_business_id' => $appointment->followup_business_id,
                    'business_name' => $appointment->followupBusiness->name ?? null,
                    'contact_person' => $appointment->followupBusiness->email ?? null,
                    'contact_number' => $appointment->followupBusiness->phone ?? null,
                    'date' => $appointment->date,
                    'time_slot' => $appointment->timeSlot ? [
                        'id' => $appointment->timeSlot->id,
                        'start_time' => $appointment->timeSlot->start_time,
                        'end_time' => $appointment->timeSlot->end_time,
                    ] : null,
                    'current_status' => $appointment->current_status,
                    'created_by' => $appointment->creator ? [
                        'id' => $appointment->creator->id,
                        'first_name' => $appointment->creator->first_name,
                        'last_name' => $appointment->creator->last_name,
                        'email' => $appointment->creator->email,
                        'username' => $appointment->creator->username,
                    ] : null,
                ];
            });

        $scheduleDetails = [
            'date' => $date,
            'appointments' => $appointments,
            'scheduled_rescheduled_appointments' => $scheduledAppointments,
            'consultations' => $consultations,
            'user_block_calendars' => $userBlockCalendars,
        ];

        return $this->successResponse($scheduleDetails, 'Schedule details retrieved successfully');
    }
}
