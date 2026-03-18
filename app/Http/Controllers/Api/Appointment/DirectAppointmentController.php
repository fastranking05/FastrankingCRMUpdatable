<?php

namespace App\Http\Controllers\Api\Appointment;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Appointment;
use App\Models\FollowupBusiness;
use App\Models\FollowupAuthPerson;
use App\Models\TimeSlot;
use App\Services\AppointmentBookingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DirectAppointmentController extends BaseApiController
{
    protected $appointmentBookingEngine;

    public function __construct(AppointmentBookingEngine $appointmentBookingEngine)
    {
        $this->appointmentBookingEngine = $appointmentBookingEngine;
    }

    /**
     * Create a direct appointment with new business and auth persons
     */
    public function createDirectAppointment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Business Details
            'business.name' => 'required|string|max:255',
            'business.category' => 'nullable|string|max:255',
            'business.type' => 'nullable|string|max:255',
            'business.website' => 'nullable|url|max:255',
            'business.phone' => 'nullable|string|unique:followup_businesses,phone',
            'business.email' => 'nullable|email|unique:followup_businesses,email',
            
            // Auth Persons (array - at least one required)
            'auth_persons' => 'required|array|min:1',
            'auth_persons.*.title' => 'nullable|string|max:50',
            'auth_persons.*.firstname' => 'required|string|max:255',
            'auth_persons.*.middlename' => 'nullable|string|max:255',
            'auth_persons.*.lastname' => 'required|string|max:255',
            'auth_persons.*.is_primary' => 'nullable|boolean',
            'auth_persons.*.designation' => 'nullable|string|max:255',
            'auth_persons.*.gender' => 'nullable|in:male,female,other',
            'auth_persons.*.dob' => 'nullable|date',
            'auth_persons.*.primaryphone' => 'nullable|string|unique:followup_auth_persons,primaryphone',
            'auth_persons.*.altphone' => 'nullable|string',
            'auth_persons.*.primarymobile' => 'nullable|string|unique:followup_auth_persons,primarymobile',
            'auth_persons.*.altmobile' => 'nullable|string|unique:followup_auth_persons,altmobile',
            'auth_persons.*.primaryemail' => 'required|email|unique:followup_auth_persons,primaryemail',
            'auth_persons.*.altemail' => 'nullable|email|unique:followup_auth_persons,altemail',
            
            // Appointment Details
            'appointment.date' => 'required|date|after_or_equal:today',
            'appointment.time_slot_id' => 'required|exists:time_slots,id',
            'appointment.current_status' => 'nullable|string|max:100',
            'appointment.status' => 'nullable|string|in:Appointment Booked,Appointment Rebooked',
            'appointment.source' => 'nullable|string|max:255',
            'appointment.notes' => 'nullable|string',
            
            // Comments (optional)
            'comments' => 'nullable|array',
            'comments.*.comment' => 'required|string|max:1000',
            'comments.*.created_by' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            // Create Business
            $businessData = $request->business;
            $businessData['created_by'] = auth()->id();
            $business = FollowupBusiness::create($businessData);

            // Create Auth Persons and associate with business
            $authPersonIds = [];
            foreach ($request->auth_persons as $personData) {
                $personData['created_by'] = auth()->id();
                $authPerson = FollowupAuthPerson::create($personData);
                $authPersonIds[] = $authPerson->id;
            }

            // Associate auth persons with business
            $business->authPersons()->attach($authPersonIds);

            // Create Appointment
            $appointmentData = $request->appointment;
            $appointmentData['followup_business_id'] = $business->id;
            $appointmentData['source'] = $appointmentData['source'] ?? 'Direct';
            $appointmentData['status'] = $appointmentData['status'] ?? 'Appointment Booked';
            $appointmentData['current_status'] = $appointmentData['current_status'] ?? 'Booked';
            $appointmentData['created_by'] = auth()->id();

            // Check time slot availability
            $timeSlot = TimeSlot::find($appointmentData['time_slot_id']);
            if (!$timeSlot || !$timeSlot->is_active) {
                return $this->errorResponse('Time slot is not available', 400);
            }

            // Check if slot is available for the date
            if (!$timeSlot->isAvailableForDate($appointmentData['date'])) {
                return $this->errorResponse('Time slot is not available for the selected date', 400);
            }

            // Create appointment
            $appointment = Appointment::create($appointmentData);

            // Create comments if provided
            if ($request->has('comments') && !empty($request->comments)) {
                foreach ($request->comments as $commentData) {
                    $commentData['followup_business_id'] = $business->id;
                    $commentData['appointment_id'] = $appointment->id;
                    $commentData['comment'] = $commentData['comment'];
                    $commentData['created_by'] = $commentData['created_by'];
                    
                    // Create comment using the FollowupComment model
                    \App\Models\FollowupComment::create($commentData);
                }
            }

            // Load complete data for response
            $business->load([
                'creator:id,first_name,last_name',
                'authPersons',
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ]);

            $appointment->load([
                'timeSlot',
                'creator:id,first_name,last_name'
            ]);

            return $this->successResponse([
                'business' => $business,
                'appointment' => $appointment
            ], 'Direct appointment created successfully', 201);
        }, 'Direct appointment creation');
    }

    /**
     * Create appointment for existing business
     */
    public function createAppointmentForExistingBusiness(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Appointment Details
            'appointment.date' => 'required|date|after_or_equal:today',
            'appointment.time_slot_id' => 'required|exists:time_slots,id',
            'appointment.current_status' => 'nullable|string|max:100',
            'appointment.status' => 'nullable|string|in:Appointment Booked,Appointment Rebooked',
            'appointment.source' => 'nullable|string|max:255',
            'appointment.notes' => 'nullable|string',
            
            // Optional new auth persons
            'auth_persons' => 'nullable|array',
            'auth_persons.*.title' => 'nullable|string|max:50',
            'auth_persons.*.firstname' => 'required|string|max:255',
            'auth_persons.*.middlename' => 'nullable|string|max:255',
            'auth_persons.*.lastname' => 'required|string|max:255',
            'auth_persons.*.is_primary' => 'nullable|boolean',
            'auth_persons.*.designation' => 'nullable|string|max:255',
            'auth_persons.*.gender' => 'nullable|in:male,female,other',
            'auth_persons.*.dob' => 'nullable|date',
            'auth_persons.*.primaryphone' => 'nullable|string|unique:followup_auth_persons,primaryphone',
            'auth_persons.*.altphone' => 'nullable|string',
            'auth_persons.*.primarymobile' => 'nullable|string|unique:followup_auth_persons,primarymobile',
            'auth_persons.*.altmobile' => 'nullable|string|unique:followup_auth_persons,altmobile',
            'auth_persons.*.primaryemail' => 'required|email|unique:followup_auth_persons,primaryemail',
            'auth_persons.*.altemail' => 'nullable|email|unique:followup_auth_persons,altemail',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $businessId) {
            // Verify business exists
            $business = FollowupBusiness::find($businessId);
            if (!$business) {
                return $this->errorResponse('Business not found', 404);
            }

            // Check if appointment already exists for this business
            $existingAppointment = Appointment::where('followup_business_id', $businessId)->first();
            if ($existingAppointment) {
                return $this->errorResponse('Appointment already exists for this business', 400);
            }

            // Create new auth persons if provided
            if ($request->has('auth_persons')) {
                $newAuthPersonIds = [];
                foreach ($request->auth_persons as $personData) {
                    $personData['created_by'] = auth()->id();
                    $authPerson = FollowupAuthPerson::create($personData);
                    $newAuthPersonIds[] = $authPerson->id;
                }
                
                // Associate new auth persons with business
                $business->authPersons()->attach($newAuthPersonIds);
            }

            // Create Appointment
            $appointmentData = $request->appointment;
            $appointmentData['followup_business_id'] = $business->id;
            $appointmentData['source'] = $appointmentData['source'] ?? 'Direct';
            $appointmentData['status'] = $appointmentData['status'] ?? 'Appointment Booked';
            $appointmentData['current_status'] = $appointmentData['current_status'] ?? 'Booked';
            $appointmentData['created_by'] = auth()->id();

            // Check time slot availability
            $timeSlot = TimeSlot::find($appointmentData['time_slot_id']);
            if (!$timeSlot || !$timeSlot->is_active) {
                return $this->errorResponse('Time slot is not available', 400);
            }

            // Check if slot is available for the date
            if (!$timeSlot->isAvailableForDate($appointmentData['date'])) {
                return $this->errorResponse('Time slot is not available for the selected date', 400);
            }

            // Create appointment
            $appointment = Appointment::create($appointmentData);

            // Create comments if provided
            if ($request->has('comments') && !empty($request->comments)) {
                foreach ($request->comments as $commentData) {
                    $commentData['followup_business_id'] = $business->id;
                    $commentData['appointment_id'] = $appointment->id;
                    $commentData['comment'] = $commentData['comment'];
                    $commentData['created_by'] = $commentData['created_by'];
                    
                    // Create comment using the FollowupComment model
                    \App\Models\FollowupComment::create($commentData);
                }
            }

            // Load complete data for response
            $business->load([
                'creator:id,first_name,last_name',
                'authPersons',
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ]);

            $appointment->load([
                'timeSlot',
                'creator:id,first_name,last_name'
            ]);

            return $this->successResponse([
                'business' => $business,
                'appointment' => $appointment
            ], 'Appointment created for existing business successfully', 201);
        }, 'Appointment creation for existing business');
    }

    /**
     * Get available time slots for direct appointment booking
     */
    public function getAvailableTimeSlots(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date|after_or_equal:today',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            $availableSlots = $this->appointmentBookingEngine->getAvailableSlots($request->date);
            
            return $this->successResponse($availableSlots, 'Available time slots retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve available time slots', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Hold a time slot temporarily (for direct booking flow)
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

        try {
            $hold = $this->appointmentBookingEngine->holdTimeSlot(
                $request->date,
                $request->time_slot_id,
                auth()->id(),
                session()->getId()
            );

            if (!$hold) {
                return $this->errorResponse('Time slot is not available', 400);
            }

            return $this->successResponse($hold, 'Time slot held successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to hold time slot', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Release a held time slot
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

        try {
            $released = $this->appointmentBookingEngine->releaseTimeSlot(
                $request->date,
                $request->time_slot_id,
                auth()->id(),
                session()->getId()
            );

            return $this->successResponse(['released' => $released], 'Time slot released successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to release time slot', 500, [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get list of direct appointments
     */
    public function index(Request $request): JsonResponse
    {
        $query = Appointment::with([
            'business:id,name,category,type,phone,email',
            'business.authPersons:id,title,firstname,lastname,designation,primaryemail,primarymobile',
            'timeSlot:id,name,start_time,end_time',
            'creator:id,first_name,last_name'
        ])->where('source', 'Direct');

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('current_status')) {
            $query->where('current_status', $request->current_status);
        }
        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        $appointments = $query->orderBy('date', 'asc')
            ->orderBy('time_slot_id', 'asc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse($appointments, 'Direct appointments retrieved successfully');
    }

    /**
     * Get single direct appointment
     */
    public function show(string $appointmentId): JsonResponse
    {
        $appointment = Appointment::with([
            'business:id,name,category,type,phone,email,website',
            'business.creator:id,first_name,last_name',
            'business.authPersons:id,title,firstname,lastname,designation,gender,dob,primaryphone,altphone,primarymobile,altmobile,primaryemail,altemail',
            'business.comments' => function ($query) {
                $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
            },
            'timeSlot:id,name,start_time,end_time,duration_minutes,max_concurrent_bookings',
            'creator:id,first_name,last_name'
        ])->where('id', $appointmentId)
        ->where('source', 'Direct')
        ->first();

        if (!$appointment) {
            return $this->errorResponse('Direct appointment not found', 404);
        }

        return $this->successResponse($appointment, 'Direct appointment retrieved successfully');
    }

    /**
     * Update direct appointment
     */
    public function update(Request $request, string $appointmentId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'appointment.date' => 'nullable|date|after_or_equal:today',
            'appointment.time_slot_id' => 'nullable|exists:time_slots,id',
            'appointment.current_status' => 'nullable|string|max:100',
            'appointment.status' => 'nullable|string|in:Appointment Booked,Appointment Rebooked',
            'appointment.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $appointmentId) {
            $appointment = Appointment::where('id', $appointmentId)
                ->where('source', 'Direct')
                ->first();

            if (!$appointment) {
                return $this->errorResponse('Direct appointment not found', 404);
            }

            $appointmentData = $request->appointment ?? [];
            
            // Check time slot availability if changing date or time slot
            if (isset($appointmentData['date']) || isset($appointmentData['time_slot_id'])) {
                $date = $appointmentData['date'] ?? $appointment->date;
                $timeSlotId = $appointmentData['time_slot_id'] ?? $appointment->time_slot_id;
                
                $timeSlot = TimeSlot::find($timeSlotId);
                if (!$timeSlot || !$timeSlot->is_active) {
                    return $this->errorResponse('Time slot is not available', 400);
                }

                // Check if slot is available for the date (excluding current appointment)
                $existingAppointments = Appointment::where('time_slot_id', $timeSlotId)
                    ->where('date', $date)
                    ->where('id', '!=', $appointment->id)
                    ->whereIn('current_status', ['Booked', 'Confirmed', 'In Progress'])
                    ->count();
                
                if ($existingAppointments >= $timeSlot->max_concurrent_bookings) {
                    return $this->errorResponse('Time slot is not available for the selected date', 400);
                }
            }

            $appointment->update($appointmentData);

            $appointment->load([
                'business:id,name,category,type,phone,email',
                'business.authPersons:id,title,firstname,lastname,designation,primaryemail,primarymobile',
                'timeSlot:id,name,start_time,end_time',
                'creator:id,first_name,last_name'
            ]);

            return $this->successResponse($appointment, 'Direct appointment updated successfully');
        }, 'Direct appointment update');
    }

    /**
     * Update appointment by business ID
     */
    public function updateAppointmentByBusiness(Request $request, int $businessId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'appointment.date' => 'nullable|date|after_or_equal:today',
            'appointment.time_slot_id' => 'nullable|exists:time_slots,id',
            'appointment.current_status' => 'nullable|string|max:100',
            'appointment.status' => 'nullable|string|in:Appointment Booked,Appointment Rebooked',
            'appointment.notes' => 'nullable|string',
            
            // Optional business update
            'business.name' => 'nullable|string|max:255',
            'business.category' => 'nullable|string|max:255',
            'business.type' => 'nullable|string|max:255',
            'business.website' => 'nullable|url|max:255',
            'business.phone' => 'nullable|string',
            'business.email' => 'nullable|email',
            
            // Optional auth persons update
            'auth_persons' => 'nullable|array',
            'auth_persons.*.id' => 'sometimes|required|exists:followup_auth_persons,id',
            'auth_persons.*.title' => 'nullable|string|max:50',
            'auth_persons.*.firstname' => 'sometimes|required|string|max:255',
            'auth_persons.*.middlename' => 'nullable|string|max:255',
            'auth_persons.*.lastname' => 'sometimes|required|string|max:255',
            'auth_persons.*.is_primary' => 'nullable|boolean',
            'auth_persons.*.designation' => 'nullable|string|max:255',
            'auth_persons.*.gender' => 'nullable|in:male,female,other',
            'auth_persons.*.dob' => 'nullable|date',
            'auth_persons.*.primaryphone' => 'nullable|string',
            'auth_persons.*.altphone' => 'nullable|string',
            'auth_persons.*.primarymobile' => 'nullable|string',
            'auth_persons.*.altmobile' => 'nullable|string',
            'auth_persons.*.primaryemail' => 'sometimes|required|email',
            'auth_persons.*.altemail' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $businessId) {
            // Find business
            $business = FollowupBusiness::find($businessId);
            if (!$business) {
                return $this->errorResponse('Business not found', 404);
            }

            // Find appointment for this business
            $appointment = Appointment::where('followup_business_id', $businessId)->first();
            if (!$appointment) {
                return $this->errorResponse('Appointment not found for this business', 404);
            }

            // Update business if provided
            if ($request->has('business')) {
                $business->update($request->business);
            }

            // Update auth persons if provided
            if ($request->has('auth_persons')) {
                // Get current auth person IDs for this business
                $currentAuthPersonIds = $business->authPersons()->pluck('followup_auth_persons.id')->toArray();
                $newAuthPersonIds = [];
                
                foreach ($request->auth_persons as $personData) {
                    if (isset($personData['id'])) {
                        // Update existing auth person
                        $person = FollowupAuthPerson::find($personData['id']);
                        if ($person) {
                            $person->update($personData);
                            $newAuthPersonIds[] = $person->id;
                        }
                    } else {
                        // Create new auth person
                        $personData['created_by'] = auth()->id();
                        $person = FollowupAuthPerson::create($personData);
                        $newAuthPersonIds[] = $person->id;
                    }
                }
                
                // Delete auth persons that are no longer in the payload
                $idsToDelete = array_diff($currentAuthPersonIds, $newAuthPersonIds);
                if (!empty($idsToDelete)) {
                    foreach ($idsToDelete as $idToDelete) {
                        $business->authPersons()->detach($idToDelete);
                        
                        $personToDelete = FollowupAuthPerson::find($idToDelete);
                        if ($personToDelete && $personToDelete->businesses()->count() === 0) {
                            $personToDelete->delete();
                        }
                    }
                }
                
                // Sync auth persons with business
                $business->authPersons()->sync($newAuthPersonIds);
            }

            // Update appointment
            $appointmentData = $request->appointment ?? [];
            
            // Check time slot availability if changing date or time slot
            if (isset($appointmentData['date']) || isset($appointmentData['time_slot_id'])) {
                $date = $appointmentData['date'] ?? $appointment->date;
                $timeSlotId = $appointmentData['time_slot_id'] ?? $appointment->time_slot_id;
                
                $timeSlot = TimeSlot::find($timeSlotId);
                if (!$timeSlot || !$timeSlot->is_active) {
                    return $this->errorResponse('Time slot is not available', 400);
                }

                // Check if slot is available for the date (excluding current appointment)
                $existingAppointments = Appointment::where('time_slot_id', $timeSlotId)
                    ->where('date', $date)
                    ->where('id', '!=', $appointment->id)
                    ->whereIn('current_status', ['Booked', 'Confirmed', 'In Progress'])
                    ->count();
                
                if ($existingAppointments >= $timeSlot->max_concurrent_bookings) {
                    return $this->errorResponse('Time slot is not available for the selected date', 400);
                }
            }

            $appointment->update($appointmentData);

            // Load complete data for response
            $business->load([
                'creator:id,first_name,last_name',
                'authPersons',
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ]);

            $appointment->load([
                'timeSlot',
                'creator:id,first_name,last_name'
            ]);

            return $this->successResponse([
                'business' => $business,
                'appointment' => $appointment
            ], 'Appointment updated successfully');
        }, 'Appointment update by business');
    }

    /**
     * Delete appointment by business ID
     */
    public function deleteAppointmentByBusiness(int $businessId): JsonResponse
    {
        return $this->executeTransaction(function () use ($businessId) {
            // Find business
            $business = FollowupBusiness::find($businessId);
            if (!$business) {
                return $this->errorResponse('Business not found', 404);
            }

            // Find appointment for this business
            $appointment = Appointment::where('followup_business_id', $businessId)->first();
            if (!$appointment) {
                return $this->errorResponse('Appointment not found for this business', 404);
            }

            // Delete appointment
            $appointment->delete();

            return $this->successResponse(null, 'Appointment deleted successfully');
        }, 'Appointment deletion by business');
    }

    /**
     * Cancel direct appointment
     */
    public function cancel(string $appointmentId): JsonResponse
    {
        return $this->executeTransaction(function () use ($appointmentId) {
            $appointment = Appointment::where('id', $appointmentId)
                ->where('source', 'Direct')
                ->first();

            if (!$appointment) {
                return $this->errorResponse('Direct appointment not found', 404);
            }

            $appointment->update([
                'current_status' => 'Cancelled',
                'status' => 'Appointment Rebooked'
            ]);

            return $this->successResponse(null, 'Direct appointment cancelled successfully');
        }, 'Direct appointment cancellation');
    }
}
