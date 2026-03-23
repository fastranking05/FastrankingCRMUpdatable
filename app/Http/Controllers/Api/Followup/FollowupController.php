<?php

namespace App\Http\Controllers\Api\Followup;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\FollowupBusiness;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupDetail;
use App\Models\Comment;
use App\Models\Appointment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FollowupController extends BaseApiController
{
    /**
     * Display a listing of complete follow-up records.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = FollowupBusiness::with([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails',
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ]);

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Filter by status (from followup_details)
            if ($request->has('status')) {
                $query->whereHas('followupDetails', function ($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }

            // Filter by name
            if ($request->has('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $followups = $query->paginate($perPage);

            return $this->successResponse($followups, 'Follow-up records retrieved successfully');
        }, 'Follow-up list retrieval');
    }

    /**
     * Store a complete follow-up record with all related data.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Business Details
            'business.name' => 'required|string|max:255',
            'business.category' => 'nullable|string|max:255',
            'business.type' => 'nullable|string|max:255',
            'business.website' => 'nullable|url|max:255',
            'business.phone' => 'nullable|string|unique:followup_businesses,phone',
            'business.email' => 'nullable|email|max:255',
            
            // Auth Persons (array)
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
            'auth_persons.*.altphone' => 'nullable|string|unique:followup_auth_persons,altphone',
            'auth_persons.*.primarymobile' => 'nullable|string|unique:followup_auth_persons,primarymobile',
            'auth_persons.*.altmobile' => 'nullable|string|unique:followup_auth_persons,altmobile',
            'auth_persons.*.primaryemail' => 'required|email|unique:followup_auth_persons,primaryemail',
            'auth_persons.*.altemail' => 'nullable|email|unique:followup_auth_persons,altemail',
            
            // Follow-up Details (array)
            'followup_details' => 'nullable|array',
            'followup_details.*.source' => 'nullable|string|max:255',
            'followup_details.*.status' => 'nullable|string|max:255',
            'followup_details.*.date' => 'nullable|date',
            'followup_details.*.time' => 'nullable|date_format:H:i',
            
            // Comments (array) - directly linked to business
            'comments' => 'nullable|array',
            'comments.*.comment' => 'required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
            
            // Appointment data (if status is Appointment Booked)
            'appointment' => 'nullable|array',
            'appointment.date' => 'required_with:appointment|date|after_or_equal:today',
            'appointment.time_slot_id' => 'required_with:appointment|exists:time_slots,id',
            'appointment.current_status' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            // Create Business
            $businessData = $request->business;
            $businessData['created_by'] = auth()->id();
            $business = FollowupBusiness::create($businessData);

            // Create Auth Persons if provided
            $authPersons = [];
            if ($request->has('auth_persons')) {
                foreach ($request->auth_persons as $personData) {
                    $personData['created_by'] = auth()->id();
                    $person = FollowupAuthPerson::create($personData);
                    $authPersons[] = $person;
                    
                    // Attach to business
                    $business->authPersons()->attach($person->id);
                }
            }

            // Create Follow-up Details if provided
            $followupDetails = [];
            if ($request->has('followup_details')) {
                foreach ($request->followup_details as $detailData) {
                    $detailData['followup_business_id'] = $business->id;
                    $detailData['created_by'] = auth()->id();
                    
                    // Let the model generate the custom ID
                    $detail = FollowupDetail::create($detailData);
                    $followupDetails[] = $detail;
                }
            }

            // Create Comments if provided (directly linked to business)
            if ($request->has('comments')) {
                foreach ($request->comments as $commentData) {
                    $business->comments()->create([
                        'comment' => $commentData['comment'],
                        'old_status' => $commentData['old_status'] ?? null,
                        'new_status' => $commentData['new_status'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Create Appointment if provided and status is Appointment Booked
            $appointment = null;
            if ($request->has('appointment')) {
                $appointmentData = $request->appointment;
                $appointmentData['followup_business_id'] = $business->id;
                $appointmentData['source'] = $appointmentData['source'] ?? 'Follow-up';
                $appointmentData['status'] = $appointmentData['status'] ?? 'Appointment Booked';
                $appointmentData['current_status'] = $appointmentData['current_status'] ?? 'Booked';
                $appointmentData['created_by'] = auth()->id();

                $appointment = Appointment::create($appointmentData);
            }

            // Load complete relationship data
            $business->load([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails',
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ]);

            // Load appointment if created
            if ($appointment) {
                $appointment->load(['timeSlot', 'creator']);
                $business->appointment = $appointment;
            }

            return $this->successResponse($business, 'Complete follow-up record created successfully', 201);
        }, 'Follow-up creation', $request->only(['business.name', 'business.email']));
    }

    /**
     * Display the specified complete follow-up record.
     */
    public function show($id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            // Handle both integer and string IDs
            $followup = is_numeric($id) ? FollowupBusiness::find($id) : FollowupBusiness::find($id);
            
            if (!$followup) {
                return $this->errorResponse('Follow-up record not found', 404);
            }

            $followup->load([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails',
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ]);

            return $this->successResponse($followup, 'Follow-up record retrieved successfully');
        }, 'Follow-up retrieval', ['followup_id' => $id]);
    }

    /**
     * Update the complete follow-up record.
     */
    public function update(Request $request, $id): JsonResponse
    {
        // Handle both integer and string IDs
        $followup = is_numeric($id) ? FollowupBusiness::find($id) : FollowupBusiness::find($id);

        if (!$followup) {
            return $this->errorResponse('Follow-up record not found', 404);
        }

        $validator = Validator::make($request->all(), [
            // Business Details
            'business.name' => 'sometimes|required|string|max:255',
            'business.category' => 'nullable|string|max:255',
            'business.type' => 'nullable|string|max:255',
            'business.website' => 'nullable|url|max:255',
            'business.phone' => 'nullable|string|unique:followup_businesses,phone,' . $id,
            'business.email' => 'nullable|email|max:255',
            
            // Auth Persons (array for sync)
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
            
            // Follow-up Details (array)
            'followup_details' => 'nullable|array',
            'followup_details.*.source' => 'nullable|string|max:255',
            'followup_details.*.status' => 'nullable|string|max:255',
            'followup_details.*.date' => 'nullable|date',
            'followup_details.*.time' => 'nullable|date_format:H:i',
            
            // Comments (array) - directly linked to business
            'comments' => 'nullable|array',
            'comments.*.comment' => 'sometimes|required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ]);

        // Custom validation for auth person uniqueness during updates
        if ($request->has('auth_persons')) {
            foreach ($request->auth_persons as $index => $personData) {
                if (isset($personData['id'])) {
                    $existingPerson = FollowupAuthPerson::find($personData['id']);
                    if ($existingPerson) {
                        // Check if phone/email is being changed to a different existing value
                        if (isset($personData['primaryphone']) && $personData['primaryphone'] !== null && $personData['primaryphone'] !== $existingPerson->primaryphone) {
                            $existingPhone = FollowupAuthPerson::where('primaryphone', $personData['primaryphone'])
                                ->where('id', '!=', $personData['id'])
                                ->first();
                            if ($existingPhone) {
                                return $this->errorResponse('Validation failed', 422, [
                                    "auth_persons.{$index}.primaryphone" => ["The phone number has already been taken."]
                                ]);
                            }
                        }
                        
                        if (isset($personData['altphone']) && $personData['altphone'] !== null && $personData['altphone'] !== $existingPerson->altphone) {
                            $existingPhone = FollowupAuthPerson::where('altphone', $personData['altphone'])
                                ->where('id', '!=', $personData['id'])
                                ->first();
                            if ($existingPhone) {
                                return $this->errorResponse('Validation failed', 422, [
                                    "auth_persons.{$index}.altphone" => ["The alternate phone has already been taken."]
                                ]);
                            }
                        }
                        
                        if (isset($personData['primarymobile']) && $personData['primarymobile'] !== null && $personData['primarymobile'] !== $existingPerson->primarymobile) {
                            $existingMobile = FollowupAuthPerson::where('primarymobile', $personData['primarymobile'])
                                ->where('id', '!=', $personData['id'])
                                ->first();
                            if ($existingMobile) {
                                return $this->errorResponse('Validation failed', 422, [
                                    "auth_persons.{$index}.primarymobile" => ["The mobile number has already been taken."]
                                ]);
                            }
                        }
                        
                        if (isset($personData['altmobile']) && $personData['altmobile'] !== null && $personData['altmobile'] !== $existingPerson->altmobile) {
                            $existingMobile = FollowupAuthPerson::where('altmobile', $personData['altmobile'])
                                ->where('id', '!=', $personData['id'])
                                ->first();
                            if ($existingMobile) {
                                return $this->errorResponse('Validation failed', 422, [
                                    "auth_persons.{$index}.altmobile" => ["The alternate mobile has already been taken."]
                                ]);
                            }
                        }
                        
                        if (isset($personData['primaryemail']) && $personData['primaryemail'] !== null && $personData['primaryemail'] !== $existingPerson->primaryemail) {
                            $existingEmail = FollowupAuthPerson::where('primaryemail', $personData['primaryemail'])
                                ->where('id', '!=', $personData['id'])
                                ->first();
                            if ($existingEmail) {
                                return $this->errorResponse('Validation failed', 422, [
                                    "auth_persons.{$index}.primaryemail" => ["The email has already been taken."]
                                ]);
                            }
                        }
                        
                        if (isset($personData['altemail']) && $personData['altemail'] !== null && $personData['altemail'] !== $existingPerson->altemail) {
                            $existingEmail = FollowupAuthPerson::where('altemail', $personData['altemail'])
                                ->where('id', '!=', $personData['id'])
                                ->first();
                            if ($existingEmail) {
                                return $this->errorResponse('Validation failed', 422, [
                                    "auth_persons.{$index}.altemail" => ["The alternate email has already been taken."]
                                ]);
                            }
                        }
                    }
                }
            }
        }

        return $this->executeTransaction(function () use ($request, $followup) {
            // Update Business
            if ($request->has('business')) {
                $followup->update($request->business);
            }

            // Update Auth Persons if provided
            if ($request->has('auth_persons')) {
                // Get current auth person IDs for this business
                $currentAuthPersonIds = $followup->authPersons()->pluck('followup_auth_persons.id')->toArray();
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
                        // Detach from business
                        $followup->authPersons()->detach($idToDelete);
                        
                        // Delete the auth person record if not associated with other businesses
                        $personToDelete = FollowupAuthPerson::find($idToDelete);
                        if ($personToDelete && $personToDelete->businesses()->count() === 0) {
                            $personToDelete->delete();
                        }
                    }
                }
                
                // Sync auth persons with business
                $followup->authPersons()->sync($newAuthPersonIds);
            }

            // Update Follow-up Details if provided - SKIP when appointment is being created
            if ($request->has('followup_details') && !$request->has('appointment')) {
                foreach ($request->followup_details as $detailData) {
                    // Always create new detail for activity tracking
                    $newDetailData = $detailData;
                    $newDetailData['followup_business_id'] = $followup->id;
                    $newDetailData['created_by'] = auth()->id();
                    
                    // Remove ID if provided to ensure new record creation
                    unset($newDetailData['id']);
                    
                    FollowupDetail::create($newDetailData);
                }
            }

            // Update Comments if provided - Allow with appointment for status tracking
            if ($request->has('comments')) {
                foreach ($request->comments as $commentData) {
                    // Always create new comment for activity tracking
                    $newCommentData = $commentData;
                    $newCommentData['followup_business_id'] = $followup->id;
                    $newCommentData['created_by'] = auth()->id();
                    
                    // Remove ID if provided to ensure new comment creation
                    unset($newCommentData['id']);
                    
                    $followup->comments()->create([
                        'comment' => $newCommentData['comment'],
                        'old_status' => $newCommentData['old_status'] ?? null,
                        'new_status' => $newCommentData['new_status'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Create or Update Appointment if provided and status is Appointment Booked
            $appointment = null;
            if ($request->has('appointment')) {
                $appointmentData = $request->appointment;
                $appointmentData['followup_business_id'] = $followup->id;
                $appointmentData['source'] = $appointmentData['source'] ?? 'Follow-up';
                $appointmentData['status'] = $appointmentData['status'] ?? 'Appointment Booked';
                $appointmentData['current_status'] = $appointmentData['current_status'] ?? 'Booked';
                $appointmentData['created_by'] = auth()->id();

                // Check if appointment already exists for this business
                $existingAppointment = Appointment::where('followup_business_id', $followup->id)->first();
                if ($existingAppointment) {
                    // Update existing appointment (don't update ID)
                    $updateData = $appointmentData;
                    unset($updateData['id']); // Remove ID from update data
                    $existingAppointment->update($updateData);
                    $appointment = $existingAppointment;
                } else {
                    // Create new appointment (let model generate ID)
                    $createData = $appointmentData;
                    unset($createData['id']); // Remove ID to let model generate it
                    $appointment = Appointment::create($createData);
                }
            }

            // Load complete relationship data
            $followup->load([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails',
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ]);

            // Load appointment if created/updated
            if ($appointment) {
                $appointment->load(['timeSlot', 'creator']);
                $followup->appointment = $appointment;
            }

            return $this->successResponse($followup, 'Complete follow-up record updated successfully');
        }, 'Follow-up update', ['followup_id' => $followup->id]);
    }

    /**
     * Remove the specified complete follow-up record.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $followup = FollowupBusiness::find($id);

            if (!$followup) {
                return $this->errorResponse('Follow-up record not found', 404);
            }

            // Delete all related data (cascade will handle most of it)
            $followup->authPersons()->detach();
            $followup->delete();

            return $this->successResponse(null, 'Complete follow-up record deleted successfully');
        }, 'Follow-up deletion', ['followup_id' => $id]);
    }

    /**
     * Get all follow-ups based on user role hierarchy
     * 
     * Hierarchy:
     * 1. Admin: Can see all follow-ups created by any user
     * 2. Manager + Lead Generation dept + has team: Can see team members' follow-ups + own
     * 3. Executive + Lead Generation dept: Can see only own follow-ups
     */
    public function allFollowups(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $user = auth()->user();
            $userId = $user->id;

            // Load user relationships
            $user->load(['roles', 'departments', 'teams']);

            // Get user role names
            $roleNames = $user->roles->pluck('name')->toArray();
            
            // Get user department names
            $departmentNames = $user->departments->pluck('name')->toArray();
            
            // Get user's team IDs
            $teamIds = $user->teams->pluck('id')->toArray();

            // Check if user is Admin (highest priority)
            $isAdmin = in_array('Admin', $roleNames);

            // Check if user is Manager with Lead Generation department and has teams
            $isManager = in_array('Manager', $roleNames);
            $isLeadGenerationDept = in_array('Lead Generation', $departmentNames);
            $hasTeams = !empty($teamIds);

            // Check if user is Executive with Lead Generation department
            $isExecutive = in_array('Executive', $roleNames);

            // Build the base query
            $query = FollowupBusiness::with([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails' => function ($query) {
                    $query->latest('date')->latest('time')->limit(1);
                },
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ]);

            // Apply hierarchy-based filters
            if ($isAdmin) {
                // Admin can see all follow-ups (no additional filter needed)
                $accessLevel = 'admin';
            } elseif ($isManager && $isLeadGenerationDept && $hasTeams) {
                // Manager + Lead Generation + has teams: see team members' follow-ups + own
                $teamUserIds = DB::table('team_user')
                    ->whereIn('team_id', $teamIds)
                    ->pluck('user_id')
                    ->toArray();
                
                // Include current user and team members
                $allowedUserIds = array_unique(array_merge($teamUserIds, [$userId]));
                
                $query->whereIn('created_by', $allowedUserIds);
                $accessLevel = 'manager_team';
            } elseif ($isExecutive && $isLeadGenerationDept) {
                // Executive + Lead Generation: see only own follow-ups
                $query->where('created_by', $userId);
                $accessLevel = 'executive_own';
            } else {
                // Default: user can only see their own follow-ups
                $query->where('created_by', $userId);
                $accessLevel = 'own';
            }

            // Apply additional filters from request
            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Filter by status (from followup_details)
            if ($request->has('status')) {
                $query->whereHas('followupDetails', function ($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }

            // Filter by name
            if ($request->has('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Filter by creator (only for admin/manager)
            if ($request->has('created_by') && in_array($accessLevel, ['admin', 'manager_team'])) {
                if ($accessLevel === 'admin') {
                    $query->where('created_by', $request->created_by);
                } elseif ($accessLevel === 'manager_team') {
                    // Manager can only filter by team members or themselves
                    if (in_array($request->created_by, $allowedUserIds)) {
                        $query->where('created_by', $request->created_by);
                    }
                }
            }

            // Filter by date range
            if ($request->has('from_date') || $request->has('to_date')) {
                $query->whereHas('followupDetails', function ($q) use ($request) {
                    if ($request->has('from_date')) {
                        $q->whereDate('date', '>=', $request->from_date);
                    }
                    if ($request->has('to_date')) {
                        $q->whereDate('date', '<=', $request->to_date);
                    }
                });
            }

            // Sort by latest followup date and time (businesses without followups come last)
            $query->orderByDesc(
                FollowupDetail::select('date')
                    ->whereColumn('followup_details.followup_business_id', 'followup_businesses.id')
                    ->latest('date')
                    ->limit(1)
            )->orderByDesc(
                FollowupDetail::select('time')
                    ->whereColumn('followup_details.followup_business_id', 'followup_businesses.id')
                    ->latest('time')
                    ->limit(1)
            )->orderByDesc('followup_businesses.created_at');

            // Pagination
            $perPage = $request->get('per_page', 15);
            $followups = $query->paginate($perPage);

            // Add metadata to response
            $response = $followups->toArray();
            $response['access_info'] = [
                'access_level' => $accessLevel,
                'user_roles' => $roleNames,
                'user_departments' => $departmentNames,
                'is_admin' => $isAdmin,
                'is_manager_lead_gen' => $isManager && $isLeadGenerationDept,
                'is_executive_lead_gen' => $isExecutive && $isLeadGenerationDept,
                'team_count' => count($teamIds),
            ];

            return $this->successResponse($response, 'Follow-up records retrieved successfully');
        }, 'All follow-ups retrieval');
    }

    /**
     * Get all follow-ups created by logged-in user
     */
    public function myFollowups(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = FollowupBusiness::with([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails' => function ($query) {
                    $query->latest('date')->latest('time')->limit(1);
                },
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ])->where('created_by', auth()->id());

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Filter by status (only if followup details exist)
            if ($request->has('status')) {
                $query->whereHas('followupDetails', function ($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }

            // Filter by name
            if ($request->has('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Sort by latest followup date and time (businesses without followups come last)
            $query->orderByDesc(
                FollowupDetail::select('date')
                    ->whereColumn('followup_details.followup_business_id', 'followup_businesses.id')
                    ->latest('date')
                    ->limit(1)
            )->orderByDesc(
                FollowupDetail::select('time')
                    ->whereColumn('followup_details.followup_business_id', 'followup_businesses.id')
                    ->latest('time')
                    ->limit(1)
            )->orderByDesc('followup_businesses.created_at'); // Businesses without followups sorted by creation date

            // Pagination
            $perPage = $request->get('per_page', 15);
            $followups = $query->paginate($perPage);

            return $this->successResponse($followups, 'My follow-ups retrieved successfully');
        }, 'My follow-ups retrieval');
    }

    /**
     * Get today's follow-ups created by logged-in user
     */
    public function todaysFollowups(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = FollowupBusiness::with([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails' => function ($query) {
                    $query->latest('date')->latest('time')->limit(1);
                },
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ])
            ->where('created_by', auth()->id())
            ->whereHas('followupDetails', function ($q) {
                $q->whereDate('date', today());
            });

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->whereHas('followupDetails', function ($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }

            // Filter by name
            if ($request->has('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Sort by followup date and time (descending)
            $query->orderByDesc(
                FollowupDetail::select('date')
                    ->whereColumn('followup_details.followup_business_id', 'followup_businesses.id')
                    ->latest('date')
                    ->limit(1)
            )->orderByDesc(
                FollowupDetail::select('time')
                    ->whereColumn('followup_details.followup_business_id', 'followup_businesses.id')
                    ->latest('time')
                    ->limit(1)
            );

            // Pagination
            $perPage = $request->get('per_page', 15);
            $followups = $query->paginate($perPage);

            return $this->successResponse($followups, 'Today\'s follow-ups retrieved successfully');
        }, 'Today\'s follow-ups retrieval');
    }
}
