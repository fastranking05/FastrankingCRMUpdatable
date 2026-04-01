<?php

namespace App\Http\Controllers\Api\Consultation;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Consultation;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsultationController extends BaseApiController
{
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
            'appointment:id,date,followup_business_id',
            'appointment.followupBusiness:id,name',
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
}
