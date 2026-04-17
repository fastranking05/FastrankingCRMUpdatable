<?php

namespace App\Http\Controllers\Api\Appointment;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\Followup\FollowupController;
use App\Models\Appointment;
use App\Models\AppointmentSetting;
use App\Models\Consultation;
use App\Models\Comment;
use App\Models\TimeSlot;
use App\Services\AppointmentBookingEngine;
use App\Services\UserAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppointmentController extends BaseApiController
{
    protected $bookingEngine;
    protected $userAssignmentService;

    public function __construct(AppointmentBookingEngine $bookingEngine, UserAssignmentService $userAssignmentService)
    {
        $this->bookingEngine = $bookingEngine;
        $this->userAssignmentService = $userAssignmentService;
    }

    /**
     * Get available time slots for a specific date
     */
    public function getAvailableSlots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $date = $request->date;
            $departmentId = $request->department_id;

            $slots = $this->bookingEngine->getAvailableSlots($date, $departmentId);
            $stats = $this->bookingEngine->getAppointmentStats($date, $departmentId);

            return $this->successResponse([
                'date' => $date,
                'available_slots' => $slots,
                'statistics' => $stats,
            ], 'Available slots retrieved successfully');
        }, 'Available slots retrieval');
    }

    /**
     * Hold a time slot temporarily
     */
    public function holdTimeSlot(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
            'time_slot_id' => 'required|exists:time_slots,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $userId = auth()->id();
            $sessionId = session()->getId() ?? uniqid();

            $result = $this->bookingEngine->holdTimeSlot(
                $request->date,
                $request->time_slot_id,
                $userId,
                $sessionId
            );

            if (!$result['success']) {
                return $this->errorResponse($result['message'], 400);
            }

            return $this->successResponse($result, 'Time slot held successfully');
        }, 'Time slot holding');
    }

    /**
     * Confirm appointment (convert temporary booking to actual appointment)
     */
    public function confirmAppointment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'followup_business_id' => 'nullable|exists:followup_businesses,id',
            'business.name' => 'required_without:followup_business_id|string|max:255',
            'business.category' => 'nullable|string|max:255',
            'business.type' => 'nullable|string|max:255',
            'business.website' => 'nullable|url|max:255',
            'business.phone' => 'nullable|string',
            'business.email' => 'nullable|email|max:255',
            'auth_persons' => 'nullable|array',
            'auth_persons.*.title' => 'nullable|string|max:50',
            'auth_persons.*.firstname' => 'required|string|max:255',
            'auth_persons.*.lastname' => 'required|string|max:255',
            'auth_persons.*.primaryemail' => 'required|email',
            'auth_persons.*.primarymobile' => 'required|string',
            'source' => 'nullable|string|max:255',
            'status' => 'nullable|in:Appointment Booked,Appointment Rebooked',
            'date' => 'required|date|after_or_equal:today',
            'time_slot_id' => 'required|exists:time_slots,id',
            'current_status' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $sessionId = session()->getId() ?? uniqid();
            
            $appointmentData = [
                'followup_business_id' => $request->followup_business_id,
                'business' => $request->business,
                'auth_persons' => $request->auth_persons,
                'source' => $request->source,
                'status' => $request->status ?? 'Appointment Booked',
                'date' => $request->date,
                'time_slot_id' => $request->time_slot_id,
                'current_status' => $request->current_status ?? 'Booked',
                'created_by' => auth()->id(),
            ];

            $result = $this->bookingEngine->confirmAppointment($appointmentData, $sessionId);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], 400);
            }

            return $this->successResponse($result['appointment'], 'Appointment confirmed successfully', 201);
        }, 'Appointment confirmation');
    }

    /**
     * Create direct appointment (without temporary booking)
     */
    public function createDirectAppointment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'followup_business_id' => 'nullable|exists:followup_businesses,id',
            'business.name' => 'required_without:followup_business_id|string|max:255',
            'business.category' => 'nullable|string|max:255',
            'business.type' => 'nullable|string|max:255',
            'business.website' => 'nullable|url|max:255',
            'business.phone' => 'nullable|string',
            'business.email' => 'nullable|email|max:255',
            'auth_persons' => 'nullable|array',
            'auth_persons.*.title' => 'nullable|string|max:50',
            'auth_persons.*.firstname' => 'required|string|max:255',
            'auth_persons.*.lastname' => 'required|string|max:255',
            'auth_persons.*.primaryemail' => 'required|email',
            'auth_persons.*.primarymobile' => 'required|string',
            'source' => 'nullable|string|max:255',
            'status' => 'nullable|in:Appointment Booked,Appointment Rebooked',
            'date' => 'required|date|after_or_equal:today',
            'time_slot_id' => 'required|exists:time_slots,id',
            'current_status' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $appointmentData = [
                'followup_business_id' => $request->followup_business_id,
                'business' => $request->business,
                'auth_persons' => $request->auth_persons,
                'source' => $request->source,
                'status' => $request->status ?? 'Appointment Booked',
                'date' => $request->date,
                'time_slot_id' => $request->time_slot_id,
                'current_status' => $request->current_status ?? 'Booked',
                'created_by' => auth()->id(),
            ];

            $result = $this->bookingEngine->createDirectAppointment($appointmentData);

            if (!$result['success']) {
                return $this->errorResponse($result['message'], 400);
            }

            return $this->successResponse($result['appointment'], 'Appointment created successfully', 201);
        }, 'Direct appointment creation');
    }

    /**
     * Release temporary booking
     */
    public function releaseTimeSlot(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'time_slot_id' => 'required|exists:time_slots,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $sessionId = session()->getId() ?? uniqid();
            
            $released = $this->bookingEngine->releaseTimeSlot(
                $request->date,
                $request->time_slot_id,
                $sessionId
            );

            if ($released) {
                return $this->successResponse(null, 'Time slot released successfully');
            } else {
                return $this->errorResponse('No temporary booking found', 404);
            }
        }, 'Time slot release');
    }

    /**
     * List appointments
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = Appointment::with([
                'followupBusiness:id,name,category,type,phone,email',
                'followupBusiness.authPersons',
                'timeSlot:id,name,start_time,end_time',
                'creator:id,first_name,last_name'
            ]);

            // Filter by date
            if ($request->has('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by current status
            if ($request->has('current_status')) {
                $query->where('current_status', $request->current_status);
            }

            // Filter by business
            if ($request->has('business_id')) {
                $query->where('followup_business_id', $request->business_id);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $appointments = $query->orderBy('date', 'desc')->orderBy('time_slot_id', 'asc')->paginate($perPage);

            return $this->successResponse($appointments, 'Appointments retrieved successfully');
        }, 'Appointments list retrieval');
    }

    /**
     * Show appointment details with all related information
     */
    public function show(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $appointment = Appointment::with([
                'followupBusiness',
                'followupBusiness.authPersons',
                'followupBusiness.creator:id,first_name,last_name,email,username',
                'followupBusiness.comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name,email,username')->orderBy('created_at', 'desc');
                },
                'timeSlot:id,name,start_time,end_time',
                'creator:id,first_name,last_name,email,username',
                'quality' => function ($query) {
                    $query->with('assignedUser:id,first_name,last_name,email,username');
                },
                'consultations' => function ($query) {
                    $query->with([
                        'assignedUser:id,first_name,last_name,email,username',
                        'closer:id,first_name,last_name,email,username',
                        'meetingSlot:id,start_time,end_time',
                        'creator:id,first_name,last_name,email,username'
                    ])->orderBy('created_at', 'desc');
                }
            ])->find($id);

            if (!$appointment) {
                return $this->errorResponse('Appointment not found', 404);
            }

            return $this->successResponse($appointment, 'Appointment retrieved successfully');
        }, 'Appointment retrieval', ['appointment_id' => $id]);
    }

    /**
     * Update appointment
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return $this->errorResponse('Appointment not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'source' => 'nullable|string|max:255',
            'status' => 'nullable|in:Appointment Booked,Appointment Rebooked',
            'current_status' => 'nullable|string|max:100',
            'date' => 'nullable|date|after_or_equal:today',
            'time_slot_id' => 'nullable|exists:time_slots,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $appointment) {
            // Check if appointment can be updated
            if (!$appointment->canBeRescheduled() && ($request->has('date') || $request->has('time_slot_id'))) {
                return $this->errorResponse('Cannot reschedule conducted or cancelled appointments', 400);
            }

            // Validate new time slot availability if changing date/time
            if ($request->has('date') || $request->has('time_slot_id')) {
                $newDate = $request->date ?? $appointment->date;
                $newTimeSlotId = $request->time_slot_id ?? $appointment->time_slot_id;

                $timeSlot = TimeSlot::find($newTimeSlotId);
                if (!$timeSlot || !$timeSlot->isAvailableForDate($newDate)) {
                    return $this->errorResponse('New time slot is not available', 400);
                }
            }

            $appointment->update($request->all());
            $appointment->load([
                'followupBusiness:id,name,category,type',
                'timeSlot:id,name,start_time,end_time',
                'creator:id,first_name,last_name'
            ]);

            return $this->successResponse($appointment, 'Appointment updated successfully');
        }, 'Appointment update', ['appointment_id' => $appointment->id]);
    }

    /**
     * Update customer availability for a consultation and add comments
     */
    public function updateCustomerAvailability(Request $request, string $id): JsonResponse
    {
        // Find the latest consultation for this appointment
        $consultation = Consultation::where('appointment_id', $id)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$consultation) {
            return $this->errorResponse('Consultation not found for this appointment', 404);
        }

        $validator = Validator::make($request->all(), [
            'is_customer_available' => 'required|boolean',
            'comments' => 'nullable|array',
            'comments.*.followup_business_id' => 'required|exists:followup_businesses,id',
            'comments.*.comment' => 'required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        // Update consultation
        $consultation->update([
            'is_customer_available' => $request->is_customer_available,
        ]);

        // Create comments if provided
        if ($request->has('comments') && is_array($request->comments)) {
            foreach ($request->comments as $commentData) {
                Comment::create([
                    'followup_business_id' => $commentData['followup_business_id'],
                    'comment' => $commentData['comment'],
                    'old_status' => $commentData['old_status'] ?? null,
                    'new_status' => $commentData['new_status'] ?? null,
                    'created_by' => auth()->id(),
                ]);
            }
        }

        // Load relationships for response
        $consultation->load([
            'appointment:id,date,followup_business_id',
            'appointment.followupBusiness:id,name',
            'meetingSlot:id,start_time,end_time',
            'assignedUser:id,first_name,last_name,username',
        ]);

        return $this->successResponse($consultation, 'Customer availability updated successfully');
    }

    /**
     * Reschedule consultation and update appointment
     */
    public function rescheduleConsultation(Request $request, string $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return $this->errorResponse('Appointment not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'is_customer_available' => 'required|boolean',
            'status' => 'required|string|max:255',
            'meeting_date' => 'required|date',
            'meeting_time_slot_id' => 'required|exists:time_slots,id',
            'appointments' => 'required|array',
            'appointments.*.date' => 'required|date',
            'appointments.*.time_slot_id' => 'required|exists:time_slots,id',
            'appointments.*.current_status' => 'required|string|max:255',
            'comments' => 'nullable|array',
            'comments.*.followup_business_id' => 'required|exists:followup_businesses,id',
            'comments.*.comment' => 'required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $appointment) {
            // Get assigned user using round robin
            $assignmentResult = $this->userAssignmentService->createAndAssignConsultation($appointment->id);
            
            if (!$assignmentResult) {
                return $this->errorResponse('Failed to assign user using round robin', 500);
            }

            // Create new consultation
            $consultation = Consultation::create([
                'appointment_id' => $appointment->id,
                'status' => $request->status,
                'is_customer_available' => $request->is_customer_available,
                'meeting_date' => $request->meeting_date,
                'meeting_slot' => $request->meeting_time_slot_id,
                'assigned_user' => $assignmentResult['assigned_user']->id,
            ]);

            // Update appointment
            $appointmentData = $request->appointments[0];
            $appointment->update([
                'date' => $appointmentData['date'],
                'time_slot_id' => $appointmentData['time_slot_id'],
                'current_status' => $appointmentData['current_status'],
            ]);

            // Create comments if provided
            if ($request->has('comments') && is_array($request->comments)) {
                foreach ($request->comments as $commentData) {
                    Comment::create([
                        'followup_business_id' => $commentData['followup_business_id'],
                        'comment' => $commentData['comment'],
                        'old_status' => $commentData['old_status'] ?? null,
                        'new_status' => $commentData['new_status'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Load relationships for response
            $consultation->load([
                'appointment:id,date,followup_business_id',
                'appointment.followupBusiness:id,name',
                'meetingSlot:id,start_time,end_time',
                'assignedUser:id,first_name,last_name,username',
            ]);

            return $this->successResponse($consultation, 'Consultation rescheduled successfully');
        }, 'Consultation reschedule', ['appointment_id' => $appointment->id]);
    }

    /**
     * Reject consultation and update appointment
     */
    public function rejectConsultation(Request $request, string $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return $this->errorResponse('Appointment not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'is_customer_available' => 'required|boolean',
            'reason' => 'required|string|max:255',
            'meeting_date' => 'required|date',
            'appointments' => 'required|array',
            'appointments.*.current_status' => 'required|string|max:255',
            'comments' => 'nullable|array',
            'comments.*.followup_business_id' => 'required|exists:followup_businesses,id',
            'comments.*.comment' => 'required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $appointment) {
            // Get assigned user using round robin
            $assignmentResult = $this->userAssignmentService->createAndAssignConsultation($appointment->id);
            
            if (!$assignmentResult) {
                return $this->errorResponse('Failed to assign user using round robin', 500);
            }

            // Create new consultation
            $consultation = Consultation::create([
                'appointment_id' => $appointment->id,
                'status' => 'rejected',
                'is_customer_available' => $request->is_customer_available,
                'reason' => $request->reason,
                'meeting_date' => $request->meeting_date,
                'assigned_user' => $assignmentResult['assigned_user']->id,
            ]);

            // Update appointment
            $appointmentData = $request->appointments[0];
            $appointment->update([
                'current_status' => $appointmentData['current_status'],
            ]);

            // Create comments if provided
            if ($request->has('comments') && is_array($request->comments)) {
                foreach ($request->comments as $commentData) {
                    Comment::create([
                        'followup_business_id' => $commentData['followup_business_id'],
                        'comment' => $commentData['comment'],
                        'old_status' => $commentData['old_status'] ?? null,
                        'new_status' => $commentData['new_status'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Load relationships for response
            $consultation->load([
                'appointment:id,date,followup_business_id',
                'appointment.followupBusiness:id,name',
                'meetingSlot:id,start_time,end_time',
                'assignedUser:id,first_name,last_name,username',
            ]);

            return $this->successResponse($consultation, 'Consultation rejected successfully');
        }, 'Consultation rejection', ['appointment_id' => $appointment->id]);
    }

    /**
     * Delete appointment
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $appointment = Appointment::find($id);

            if (!$appointment) {
                return $this->errorResponse('Appointment not found', 404);
            }

            $appointment->delete();

            return $this->successResponse(null, 'Appointment deleted successfully');
        }, 'Appointment deletion', ['appointment_id' => $id]);
    }
}
