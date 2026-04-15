<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Consultation;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Department;
use App\Models\Comment;
use App\Services\UserAssignmentService;
use App\Services\DateRangeFilterService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ConsultationController extends BaseApiController
{
    private UserAssignmentService $userAssignmentService;
    private DateRangeFilterService $dateRangeFilterService;

    public function __construct(
        UserAssignmentService $userAssignmentService,
        DateRangeFilterService $dateRangeFilterService
    ) {
        $this->userAssignmentService = $userAssignmentService;
        $this->dateRangeFilterService = $dateRangeFilterService;
    }

    /**
     * Apply user role-based filtering to consultation query
     */
    private function applyUserRoleFilter($query)
    {
        $user = auth()->user();
        
        // Get user's role and department
        $userRole = $user->roles->first()->name ?? null;
        $userDepartment = $user->departments->first()->name ?? null;
        
        // If user is executive and in sales department, show only assigned consultations
        if ($userRole === 'executive' && $userDepartment === 'Sales') {
            $query->where('assigned_user', $user->id);
        }
        // If user is not executive (manager, director) and in sales department, show team and own consultations
        elseif ($userDepartment === 'Sales' && in_array($userRole, ['manager', 'director'])) {
            // Get team members (users in the same department)
            $teamUserIds = User::whereHas('departments', function($query) use ($userDepartment) {
                $query->where('name', $userDepartment);
            })->pluck('id')->toArray();
            
            $query->whereIn('assigned_user', $teamUserIds);
        }
        
        return $query;
    }

    /**
     * Get latest consultation per appointment
     */
    private function getLatestConsultations($query)
    {
        return $query->select('consultations.*')
            ->join(DB::raw('(SELECT appointment_id, MAX(created_at) as max_created_at 
                           FROM consultations 
                           GROUP BY appointment_id) latest'), function($join) {
                $join->on('consultations.appointment_id', '=', 'latest.appointment_id')
                     ->on('consultations.created_at', '=', 'latest.max_created_at');
            });
    }
    /**
     * Display a listing of consultations.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Consultation::with([
            'appointment:id,date,followup_business_id',
            'appointment.followupBusiness:id,name',
            'rescheduleSlot:id,start_time,end_time',
            'closer:id,first_name,last_name,username',
            'assignedUser:id,first_name,last_name,username',
            'creator:id,first_name,last_name,username',
        ]);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('appointment_id')) {
            $query->where('appointment_id', $request->input('appointment_id'));
        }

        if ($request->has('assigned_user')) {
            $query->where('assigned_user', $request->input('assigned_user'));
        }

        // Date range filter
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $consultations = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse($consultations, 'Consultations retrieved successfully');
    }

    /**
     * Store a newly created consultation.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'appointment_id' => 'required|exists:appointments,id',
            'status' => 'required|string|max:50',
            'custom_status' => 'nullable|string|max:50',
            'reason' => 'nullable|string',
            'reschedule_date' => 'nullable|date',
            'reschedule_slot' => 'nullable|exists:time_slots,id',
            'assigned_user' => 'nullable|exists:users,id',
            'conducted_date' => 'nullable|date',
            'is_customer_available' => 'nullable|boolean',
            'comments' => 'nullable|array',
            'comments.*.comment' => 'sometimes|required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $consultation = Consultation::create([
            'appointment_id' => $request->appointment_id,
            'status' => $request->status,
            'custom_status' => $request->custom_status,
            'reason' => $request->reason,
            'reschedule_date' => $request->reschedule_date,
            'reschedule_slot' => $request->reschedule_slot,
            'assigned_user' => $request->assigned_user,
            'conducted_date' => $request->conducted_date,
            'is_customer_available' => $request->is_customer_available ?? 0,
        ]);

        // Create comments if provided
        if ($request->has('comments') && is_array($request->comments)) {
            $appointment = Appointment::find($request->appointment_id);
            if ($appointment && $appointment->followup_business_id) {
                foreach ($request->comments as $commentData) {
                    Comment::create([
                        'followup_business_id' => $appointment->followup_business_id,
                        'comment' => $commentData['comment'] ?? null,
                        'old_status' => $commentData['old_status'] ?? null,
                        'new_status' => $commentData['new_status'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
        }

        // Update appointment current_status with consultation status
        $appointment = Appointment::find($request->appointment_id);
        if ($appointment) {
            $appointment->update([
                'current_status' => $request->status,
            ]);
        }

        // Load relationships for response
        $consultation->load([
            'appointment:id,date,followup_business_id',
            'appointment.followupBusiness:id,name',
            'rescheduleSlot:id,start_time,end_time',
            'assignedUser:id,first_name,last_name,username',
        ]);

        return $this->successResponse($consultation, 'Consultation created successfully', 201);
    }

    /**
     * Display the specified consultation.
     */
    public function show(int $id): JsonResponse
    {
        $consultation = Consultation::with([
            'appointment' => function($query) {
                $query->with([
                    'followupBusiness' => function($query) {
                        $query->with(['authPersons', 'comments']);
                    },
                    'timeSlot',
                    'quality',
                    'creator:id,first_name,last_name,email,username'
                ]);
            },
            'rescheduleSlot:id,start_time,end_time',
            'closer:id,first_name,last_name,username',
            'assignedUser:id,first_name,last_name,username',
            'creator:id,first_name,last_name,username',
        ])->find($id);

        if (!$consultation) {
            return $this->errorResponse('Consultation not found', 404);
        }

        return $this->successResponse($consultation, 'Consultation retrieved successfully');
    }

    /**
     * Update the specified consultation.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $consultation = Consultation::find($id);

        if (!$consultation) {
            return $this->errorResponse('Consultation not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|string|max:50',
            'custom_status' => 'sometimes|nullable|string|max:50',
            'reason' => 'sometimes|nullable|string',
            'reschedule_date' => 'sometimes|nullable|date',
            'reschedule_slot' => 'sometimes|nullable|exists:time_slots,id',
            'assigned_user' => 'sometimes|nullable|exists:users,id',
            'conducted_date' => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $consultation->update($request->only([
            'status',
            'custom_status',
            'reason',
            'reschedule_date',
            'reschedule_slot',
            'assigned_user',
            'conducted_date',
        ]));

        // Load relationships for response
        $consultation->load([
            'appointment:id,date,followup_business_id',
            'appointment.followupBusiness:id,name',
            'rescheduleSlot:id,start_time,end_time',
            'closer:id,first_name,last_name,username',
            'assignedUser:id,first_name,last_name,username',
        ]);

        return $this->successResponse($consultation, 'Consultation updated successfully');
    }

    /**
     * Remove the specified consultation.
     */
    public function destroy(int $id): JsonResponse
    {
        $consultation = Consultation::find($id);

        if (!$consultation) {
            return $this->errorResponse('Consultation not found', 404);
        }

        $consultation->delete();

        return $this->successResponse(null, 'Consultation deleted successfully');
    }

    /**
     * Get consultations for a specific appointment
     */
    public function getByAppointment(string $appointmentId): JsonResponse
    {
        $consultations = Consultation::with([
            'appointment:id,date,followup_business_id',
            'appointment.followupBusiness:id,name',
            'rescheduleSlot:id,start_time,end_time',
            'closer:id,first_name,last_name,username',
            'assignedUser:id,first_name,last_name,username',
            'creator:id,first_name,last_name,username',
        ])->where('appointment_id', $appointmentId)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse($consultations, 'Consultations for appointment retrieved successfully');
    }

    /**
     * Close consultation (mark as completed)
     */
    public function closeConsultation(Request $request, int $id): JsonResponse
    {
        $consultation = Consultation::find($id);

        if (!$consultation) {
            return $this->errorResponse('Consultation not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'conducted_date' => 'required|date',
            'reason' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $consultation->update([
            'conducted_date' => $request->conducted_date,
            'reason' => $request->reason,
            'closer' => auth()->id(),
        ]);

        return $this->successResponse($consultation, 'Consultation closed successfully');
    }

    /**
     * Get scheduled consultations (status: scheduled, rescheduled)
     */
    public function getScheduledConsultations(Request $request): JsonResponse
    {
        $query = Consultation::with([
            'appointment' => function($query) {
                $query->with([
                    'followupBusiness' => function($query) {
                        $query->with(['authPersons']);
                    },
                    'timeSlot'
                ]);
            },
            'rescheduleSlot:id,start_time,end_time',
            'closer:id,first_name,last_name,username',
            'assignedUser:id,first_name,last_name,username',
            'creator:id,first_name,last_name,username',
        ]);

        // Filter for scheduled and rescheduled status
        $query->whereIn('status', ['scheduled', 'rescheduled']);

        // Apply user role-based filtering
        $this->applyUserRoleFilter($query);

        // Get latest consultation per appointment
        $this->getLatestConsultations($query);

        $consultations = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse($consultations, 'Scheduled consultations retrieved successfully');
    }

    /**
     * Get conducted consultations (status: conducted)
     */
    public function getConductedConsultations(Request $request): JsonResponse
    {
        $query = Consultation::with([
            'appointment' => function($query) {
                $query->with([
                    'followupBusiness' => function($query) {
                        $query->with(['authPersons']);
                    },
                    'timeSlot'
                ]);
            },
            'rescheduleSlot:id,start_time,end_time',
            'closer:id,first_name,last_name,username',
            'assignedUser:id,first_name,last_name,username',
            'creator:id,first_name,last_name,username',
        ]);

        // Filter for conducted status
        $query->where('status', 'conducted');

        // Apply user role-based filtering
        $this->applyUserRoleFilter($query);

        // Get latest consultation per appointment
        $this->getLatestConsultations($query);

        $consultations = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse($consultations, 'Conducted consultations retrieved successfully');
    }

    /**
     * Get not conducted consultations (status not conducted)
     */
    public function getNotConductedConsultations(Request $request): JsonResponse
    {
        $query = Consultation::with([
            'appointment' => function($query) {
                $query->with([
                    'followupBusiness' => function($query) {
                        $query->with(['authPersons']);
                    },
                    'timeSlot'
                ]);
            },
            'rescheduleSlot:id,start_time,end_time',
            'closer:id,first_name,last_name,username',
            'assignedUser:id,first_name,last_name,username',
            'creator:id,first_name,last_name,username',
        ]);

        // Filter for not conducted status
        $query->where('status', '=', 'Not Conducted');

        // Apply user role-based filtering
        $this->applyUserRoleFilter($query);

        // Get latest consultation per appointment
        $this->getLatestConsultations($query);

        $consultations = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse($consultations, 'Not conducted consultations retrieved successfully');
    }

    /**
     * Get today's consultations (scheduled/rescheduled with today's appointment date)
     */
    public function getTodayConsultations(Request $request): JsonResponse
    {
        $query = Consultation::with([
            'appointment' => function($query) {
                $query->with([
                    'followupBusiness' => function($query) {
                        $query->with(['authPersons']);
                    },
                    'timeSlot'
                ]);
            },
            'rescheduleSlot:id,start_time,end_time',
            'closer:id,first_name,last_name,username',
            'assignedUser:id,first_name,last_name,username',
            'creator:id,first_name,last_name,username',
        ]);

        // Filter for scheduled and rescheduled status
        $query->whereIn('status', ['scheduled', 'rescheduled']);

        // Filter for today's appointment date
        $query->whereHas('appointment', function($q) {
            $q->whereDate('date', today());
        });

        // Apply user role-based filtering
        $this->applyUserRoleFilter($query);

        // Get latest consultation per appointment
        $this->getLatestConsultations($query);

        $consultations = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->successResponse($consultations, 'Today\'s consultations retrieved successfully');
    }

    /**
     * Filter consultations with comprehensive filtering options
     */
    public function filter(Request $request): JsonResponse
    {
        // Build base query with all required relationships
        $query = Consultation::with([
            'appointment',
            'appointment.followupBusiness.authPersons',
            'appointment.timeSlot',
            'rescheduleSlot',
            'assignedUser',
            'creator',
        ]);

        // Get appointment IDs from date filter if present
        $appointmentDateFilterIds = null;
        if ($request->has('appointments') && is_array($request->input('appointments'))) {
            $appointmentDateFilterIds = $this->getAppointmentDateFilterIds($request->input('appointments'));
        }

        // Get latest consultation record IDs for each appointment
        // If appointment date filter is present, only get latest consultation IDs for those appointments
        $latestConsultationIdsQuery = Consultation::select(DB::raw('MAX(id) as id'))
            ->groupBy('appointment_id');

        if ($appointmentDateFilterIds !== null && count($appointmentDateFilterIds) > 0) {
            $latestConsultationIdsQuery->whereIn('appointment_id', $appointmentDateFilterIds);
        }

        $latestConsultationIds = $latestConsultationIdsQuery->pluck('id')->toArray();

        $query->whereIn('id', $latestConsultationIds);

        // Apply flexible filters using DateRangeFilterService
        // Skip date_filter if appointment date filter is active to avoid conflicts
        $filterOptions = [
            'date_column' => 'created_at',
            'user_column' => 'assigned_user',
            'status_column' => 'status',
            'search_columns' => ['appointment_id', 'appointment.followupBusiness.name']
        ];

        // If appointment date filter is active, skip the date filter in DateRangeFilterService
        if ($request->has('appointments') && is_array($request->input('appointments'))) {
            $filterOptions['skip_date_filter'] = true;
        }

        $query = $this->dateRangeFilterService->applyFilters($query, $request, $filterOptions);

        // Apply additional specific filters
        if ($request->has('custom_status')) {
            $query->where('custom_status', $request->input('custom_status'));
        }

        if ($request->has('is_customer_available')) {
            $query->where('is_customer_available', $request->input('is_customer_available'));
        }

        $consultations = $query->orderBy('created_at', 'desc')
            ->get();

        // Transform data to match desired response format
        $transformedData = $consultations->map(function ($consultation) {
            return [
                'id' => $consultation->id,
                'appointment_id' => $consultation->appointment_id,
                'status' => $consultation->status,
                'custom_status' => $consultation->custom_status,
                'reason' => $consultation->reason,
                'assigned_user' => [
                    'id' => $consultation->assignedUser->id ?? null,
                    'first_name' => $consultation->assignedUser->first_name ?? null,
                    'last_name' => $consultation->assignedUser->last_name ?? null,
                    'email' => $consultation->assignedUser->email ?? null,
                ],
                'reschedule_date' => $consultation->reschedule_date,
                'reschedule_slot' => $consultation->rescheduleSlot ? [
                    'id' => $consultation->rescheduleSlot->id,
                    'start_time' => $consultation->rescheduleSlot->start_time,
                    'end_time' => $consultation->rescheduleSlot->end_time,
                ] : null,
                'conducted_date' => $consultation->conducted_date,
                'is_customer_available' => $consultation->is_customer_available,
                'created_at' => $consultation->created_at,
                'updated_at' => $consultation->updated_at,
                'business' => $consultation->appointment->followupBusiness ? [
                    'id' => $consultation->appointment->followupBusiness->id,
                    'name' => $consultation->appointment->followupBusiness->name,
                    'category' => $consultation->appointment->followupBusiness->category,
                    'type' => $consultation->appointment->followupBusiness->type,
                    'website' => $consultation->appointment->followupBusiness->website,
                    'phone' => $consultation->appointment->followupBusiness->phone,
                    'email' => $consultation->appointment->followupBusiness->email,
                    'auth_persons' => $consultation->appointment->followupBusiness->authPersons->map(function ($person) {
                        return [
                            'id' => $person->id,
                            'title' => $person->title,
                            'firstname' => $person->firstname,
                            'middlename' => $person->middlename,
                            'lastname' => $person->lastname,
                            'designation' => $person->designation,
                            'primaryemail' => $person->primaryemail,
                            'primarymobile' => $person->primarymobile,
                            'is_primary' => $person->pivot->is_primary ?? 0,
                        ];
                    })->toArray(),
                ] : null,
                'appointment_date' => $consultation->appointment->date,
                'appointment_source' => $consultation->appointment->source,
                'appointment_current_status' => $consultation->appointment->current_status,
                'appointment_slot' => $consultation->appointment->timeSlot ? [
                    'id' => $consultation->appointment->timeSlot->id,
                    'start_time' => $consultation->appointment->timeSlot->start_time,
                    'end_time' => $consultation->appointment->timeSlot->end_time,
                ] : null,
            ];
        })->toArray();

        return $this->successResponse($transformedData, 'All consultation data retrieved successfully');
    }

    /**
     * Get appointment IDs matching date filter
     */
    private function getAppointmentDateFilterIds($appointments): ?array
    {
        $dateFilter = null;
        $customStartDate = null;
        $customEndDate = null;

        // Extract date filter from appointments array
        if (is_array($appointments) && count($appointments) > 0) {
            $firstAppointment = $appointments[0];
            if (is_array($firstAppointment) && isset($firstAppointment['date'])) {
                $dateFilter = $firstAppointment['date'];
            }
            if (is_array($firstAppointment) && isset($firstAppointment['custom_start_date'])) {
                $customStartDate = $firstAppointment['custom_start_date'];
            }
            if (is_array($firstAppointment) && isset($firstAppointment['custom_end_date'])) {
                $customEndDate = $firstAppointment['custom_end_date'];
            }
        }

        if (!$dateFilter && !$customStartDate) {
            return null;
        }

        // Build date condition for appointments table
        $dateCondition = '';
        $bindings = [];

        switch ($dateFilter) {
            case 'today':
                $todayDate = Carbon::today()->toDateString();
                $dateCondition = 'DATE(date) = ?';
                $bindings[] = $todayDate;
                break;

            case 'yesterday':
                $yesterdayDate = Carbon::yesterday()->toDateString();
                $dateCondition = 'DATE(date) = ?';
                $bindings[] = $yesterdayDate;
                break;

            case 'this_week':
                $dateCondition = 'DATE(date) BETWEEN ? AND ?';
                $bindings[] = Carbon::now()->startOfWeek()->toDateString();
                $bindings[] = Carbon::now()->endOfWeek()->toDateString();
                break;

            case 'last_week':
                $dateCondition = 'DATE(date) BETWEEN ? AND ?';
                $bindings[] = Carbon::now()->subWeek()->startOfWeek()->toDateString();
                $bindings[] = Carbon::now()->subWeek()->endOfWeek()->toDateString();
                break;

            case 'this_month':
                $dateCondition = 'MONTH(date) = ? AND YEAR(date) = ?';
                $bindings[] = Carbon::now()->month;
                $bindings[] = Carbon::now()->year;
                break;

            case 'last_month':
                $dateCondition = 'MONTH(date) = ? AND YEAR(date) = ?';
                $bindings[] = Carbon::now()->subMonth()->month;
                $bindings[] = Carbon::now()->subMonth()->year;
                break;

            case 'this_year':
                $dateCondition = 'YEAR(date) = ?';
                $bindings[] = Carbon::now()->year;
                break;

            case 'last_year':
                $dateCondition = 'YEAR(date) = ?';
                $bindings[] = Carbon::now()->subYear()->year;
                break;

            case 'custom':
                if ($customStartDate && $customEndDate) {
                    $dateCondition = 'DATE(date) BETWEEN ? AND ?';
                    $bindings[] = Carbon::parse($customStartDate)->toDateString();
                    $bindings[] = Carbon::parse($customEndDate)->toDateString();
                } elseif ($customStartDate) {
                    $dateCondition = 'DATE(date) >= ?';
                    $bindings[] = Carbon::parse($customStartDate)->toDateString();
                } elseif ($customEndDate) {
                    $dateCondition = 'DATE(date) <= ?';
                    $bindings[] = Carbon::parse($customEndDate)->toDateString();
                }
                break;
        }

        if (empty($dateCondition)) {
            return null;
        }

        // Execute raw query to get appointment IDs
        $appointmentIds = DB::select(
            "SELECT id FROM appointments WHERE " . $dateCondition,
            $bindings
        );

        return array_map(function ($row) {
            return $row->id;
        }, $appointmentIds);
    }

    /**
     * Get filter options for consultations
     */
    public function getFilterOptions(): JsonResponse
    {
        $filterOptions = [
            'date_filters' => [
                'today' => 'Today',
                'yesterday' => 'Yesterday',
                'this_week' => 'This Week',
                'last_week' => 'Last Week',
                'this_month' => 'This Month',
                'last_month' => 'Last Month',
                'this_year' => 'This Year',
                'last_year' => 'Last Year',
                'custom' => 'Custom Range'
            ],
            'date_columns' => [
                'created_at' => 'Created Date',
                'updated_at' => 'Updated Date'
            ],
            'status_options' => [
                'scheduled',
                'rescheduled',
                'conducted',
                'cancelled',
                'pending',
                'in_progress',
                'completed'
            ],
            'custom_status_options' => [
                'Pending Review',
                'Awaiting Confirmation',
                'Confirmed',
                'In Progress',
                'Completed',
                'Cancelled',
                'Rescheduled'
            ],
            'is_customer_available_options' => [
                0 => 'Not Available',
                1 => 'Available'
            ]
        ];

        return $this->successResponse($filterOptions, 'Filter options retrieved successfully');
    }
}
