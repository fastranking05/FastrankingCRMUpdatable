<?php

namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\FollowupBusiness;
use App\Models\FollowupAuthPerson;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeadsController extends BaseApiController
{
    /**
     * Create lead with business, auth persons, and comments
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Business details
            'business_name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|unique:followup_businesses,phone',
            'email' => 'nullable|email|max:255',
            
            // Auth persons array
            'auth_persons' => 'required|array',
            'auth_persons.*.title' => 'nullable|string|max:50',
            'auth_persons.*.firstname' => 'required|string|max:255',
            'auth_persons.*.middlename' => 'nullable|string|max:255',
            'auth_persons.*.lastname' => 'required|string|max:255',
            'auth_persons.*.is_primary' => 'nullable|boolean',
            'auth_persons.*.designation' => 'nullable|string|max:255',
            'auth_persons.*.gender' => 'nullable|in:male,female,other',
            'auth_persons.*.dob' => 'nullable|date',
            'auth_persons.*.primaryphone' => 'nullable|string',
            'auth_persons.*.altphone' => 'nullable|string',
            'auth_persons.*.primarymobile' => 'nullable|string',
            'auth_persons.*.altmobile' => 'nullable|string',
            'auth_persons.*.primaryemail' => 'required|email',
            'auth_persons.*.altemail' => 'nullable|email',
            
            // Comments array
            'comments' => 'nullable|array',
            'comments.*.followup_business_id' => 'nullable|exists:followup_businesses,id',
            'comments.*.comment' => 'required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            // Create business
            $business = FollowupBusiness::create([
                'name' => $request->business_name,
                'category' => $request->category,
                'type' => $request->type,
                'website' => $request->website,
                'phone' => $request->phone,
                'email' => $request->email,
                'created_by' => auth()->id(),
            ]);

            // Create auth persons and attach to business
            $authPersonIds = [];
            foreach ($request->auth_persons as $authPersonData) {
                $authPerson = FollowupAuthPerson::create([
                    'title' => $authPersonData['title'] ?? null,
                    'firstname' => $authPersonData['firstname'],
                    'middlename' => $authPersonData['middlename'] ?? null,
                    'lastname' => $authPersonData['lastname'],
                    'is_primary' => $authPersonData['is_primary'] ?? false,
                    'designation' => $authPersonData['designation'] ?? null,
                    'gender' => $authPersonData['gender'] ?? null,
                    'dob' => $authPersonData['dob'] ?? null,
                    'primaryphone' => $authPersonData['primaryphone'] ?? null,
                    'altphone' => $authPersonData['altphone'] ?? null,
                    'primarymobile' => $authPersonData['primarymobile'] ?? null,
                    'altmobile' => $authPersonData['altmobile'] ?? null,
                    'primaryemail' => $authPersonData['primaryemail'],
                    'altemail' => $authPersonData['altemail'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                $authPersonIds[] = $authPerson->id;
            }

            // Attach auth persons to business
            $business->authPersons()->attach($authPersonIds);

            // Create comments if provided
            if ($request->has('comments') && is_array($request->comments)) {
                foreach ($request->comments as $commentData) {
                    Comment::create([
                        'followup_business_id' => $commentData['followup_business_id'] ?? $business->id,
                        'comment' => $commentData['comment'],
                        'old_status' => $commentData['old_status'] ?? null,
                        'new_status' => $commentData['new_status'] ?? null,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            // Load relationships for response
            $business->load(['creator:id,first_name,last_name', 'authPersons']);

            return $this->successResponse($business, 'Lead created successfully', 201);
        }, 'Lead creation', $request->only(['business_name', 'email']));
    }

    /**
     * Get all leads with role-based hierarchy access
     */
    public function getAllLeads(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $user->load(['roles', 'teams', 'departments']);
        $query = $this->getLeadsBaseQuery();
        $query = $this->applyRoleBasedFilters($query, $user);
        $query = $this->applyRequestFilters($query, $request);
        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        $leads = $query->paginate($perPage);

        return $this->successResponse([
            'leads' => $leads,
            'user_role' => $this->getUserRoleInfo($user),
            'access_level' => $this->determineAccessLevel($user)
        ], 'All leads retrieved successfully');
    }

    /**
     * Get my leads (only created by logged-in user)
     */
    public function getMyLeads(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $query = $this->getLeadsBaseQuery();
        $query->where('created_by', $user->id);
        $query = $this->applyRequestFilters($query, $request);
        $query->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', 15);
        $leads = $query->paginate($perPage);

        return $this->successResponse([
            'leads' => $leads,
            'created_by' => $user->id,
            'user_name' => $user->first_name . ' ' . $user->last_name
        ], 'My leads retrieved successfully');
    }

    /**
     * Display a listing of leads (legacy)
     */
    public function index(Request $request): JsonResponse
    {
        return $this->getAllLeads($request);
    }

    /**
     * Display the specified lead with all related data.
     */
    public function show(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $business = FollowupBusiness::with([
                'creator:id,first_name,last_name',
                'authPersons:id,title,firstname,lastname,designation,primaryemail,primarymobile,is_primary',
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                },
                'followupDetails',
                'emails' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                },
                'appointments' => function ($query) {
                    $query->with([
                        'timeSlot:id,name,start_time,end_time,duration_minutes',
                        'creator:id,first_name,last_name',
                        'quality',
                        'consultations' => function ($consultationQuery) {
                            $consultationQuery->with([
                                'meetingSlot:id,start_time,end_time',
                                'assignedUser:id,first_name,last_name,username'
                            ])->orderBy('created_at', 'desc');
                        }
                    ])->orderBy('date', 'desc')->orderBy('time_slot_id', 'desc');
                }
            ])->find($id);

            if (!$business) {
                return $this->errorResponse('Lead not found', 404);
            }

            return $this->successResponse($business, 'Lead retrieved successfully');
        }, 'Lead retrieval', ['lead_id' => $id]);
    }

    /**
     * Update the specified lead.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $business = FollowupBusiness::find($id);

        if (!$business) {
            return $this->errorResponse('Lead not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'business_name' => 'sometimes|required|string|max:255',
            'category' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|unique:followup_businesses,phone,' . $id,
            'email' => 'nullable|email|max:255',
            'auth_person_ids' => 'nullable|array',
            'auth_person_ids.*' => 'exists:followup_auth_persons,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $business) {
            $updateData = [];
            
            if ($request->has('business_name')) {
                $updateData['name'] = $request->business_name;
            }
            if ($request->has('category')) {
                $updateData['category'] = $request->category;
            }
            if ($request->has('type')) {
                $updateData['type'] = $request->type;
            }
            if ($request->has('website')) {
                $updateData['website'] = $request->website;
            }
            if ($request->has('phone')) {
                $updateData['phone'] = $request->phone;
            }
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }

            $business->update($updateData);

            // Sync authorized persons if provided
            if ($request->has('auth_person_ids')) {
                $business->authPersons()->sync($request->auth_person_ids);
            }

            $business->load(['creator:id,first_name,last_name', 'authPersons']);

            return $this->successResponse($business, 'Lead updated successfully');
        }, 'Lead update', ['lead_id' => $business->id]);
    }

    /**
     * Remove the specified lead.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $business = FollowupBusiness::find($id);

            if (!$business) {
                return $this->errorResponse('Lead not found', 404);
            }

            // Detach all relationships
            $business->authPersons()->detach();

            $business->delete();

            return $this->successResponse(null, 'Lead deleted successfully');
        }, 'Lead deletion', ['lead_id' => $id]);
    }

    private function getLeadsBaseQuery()
    {
        return FollowupBusiness::with(['creator:id,first_name,last_name', 'authPersons:id,title,firstname,lastname,designation,primaryemail,primarymobile,is_primary', 'comments']);
    }

    private function applyRoleBasedFilters($query, User $user)
    {
        $accessLevel = $this->determineAccessLevel($user);
        if ($accessLevel === 'admin') return $query;
        
        if ($accessLevel === 'manager') {
            $teamMemberIds = $this->getTeamMemberIds($user);
            $teamMemberIds[] = $user->id;
            return $query->whereIn('created_by', $teamMemberIds);
        }
        
        return $query->where('created_by', $user->id);
    }

    private function determineAccessLevel(User $user): string
    {
        $isAdmin = $user->roles->contains(fn($r) => in_array(strtolower($r->name), ['admin', 'superadmin', 'super_admin']));
        if ($isAdmin || strtolower($user->user_type) === 'admin') return 'admin';
        
        $isLeadGen = $user->departments->contains(fn($d) => in_array(strtolower($d->name), ['lead generation', 'lead_generation', 'leadgeneration']));
        $isManager = $user->roles->contains(fn($r) => in_array(strtolower($r->name), ['manager', 'team manager', 'team_manager']));
        $hasTeams = $user->teams->isNotEmpty();
        
        if ($isManager && $isLeadGen && $hasTeams) return 'manager';
        if ($hasTeams) return 'manager';
        return 'executive';
    }

    private function getTeamMemberIds(User $user): array
    {
        $teamIds = $user->teams->pluck('id')->toArray();
        if (empty($teamIds)) return [];
        return DB::table('team_user')->whereIn('team_id', $teamIds)->where('user_id', '!=', $user->id)->distinct()->pluck('user_id')->toArray();
    }

    private function applyRequestFilters($query, Request $request)
    {
        if ($request->has('category')) $query->where('category', $request->category);
        if ($request->has('type')) $query->where('type', $request->type);
        if ($request->has('name')) $query->where('name', 'like', '%' . $request->name . '%');
        return $query;
    }

    private function getUserRoleInfo(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->first_name . ' ' . $user->last_name, 'user_type' => $user->user_type];
    }
}
