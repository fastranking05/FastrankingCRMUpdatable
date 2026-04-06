<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Consultation;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Department;
use App\Services\UserAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ConsultationController extends BaseApiController
{
    private UserAssignmentService $userAssignmentService;

    public function __construct(UserAssignmentService $userAssignmentService)
    {
        $this->userAssignmentService = $userAssignmentService;
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
        ]);

        // Load relationships for response
        $consultation->load([
            'appointment:id,date,followup_business_id',
            'appointment.followupBusiness:id,name',
            'rescheduleSlot:id,start_time,end_time',
            'closer:id,first_name,last_name,username',
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
                    'quality'
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
}
