<?php

namespace App\Http\Controllers\Api\Email;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Email;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailController extends BaseApiController
{
    /**
     * Display a listing of emails.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Email::with(['followupBusiness:id,name', 'creator:id,first_name,last_name']);

        // Filter by followup business
        if ($request->has('followup_business_id')) {
            $query->where('followup_business_id', $request->followup_business_id);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by creator
        if ($request->has('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        // Order by latest
        $query->orderBy('created_at', 'desc');

        // Pagination
        $perPage = $request->get('per_page', 15);
        $emails = $query->paginate($perPage);

        return $this->successResponse($emails, 'Emails retrieved successfully');
    }

    /**
     * Get all emails with role-based hierarchy access
     * 
     * Hierarchy:
     * 1. Admin: Can see all emails
     * 2. Manager (Lead Generation dept + has team): Can see team members' emails + own
     * 3. Executive (Lead Generation dept): Can see only own emails
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllEmails(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Load user relationships
            $user->load(['roles', 'teams', 'departments']);

            // Get base query with eager loaded relationships
            $query = $this->getEmailBaseQuery();

            // Apply role-based filters
            $query = $this->applyRoleBasedFilters($query, $user);

            // Apply additional filters from request
            $query = $this->applyRequestFilters($query, $request);

            // Order by created_at descending
            $query->orderBy('created_at', 'desc');

            // Paginate results
            $perPage = $request->get('per_page', 15);
            $emails = $query->paginate($perPage);

            return $this->successResponse([
                'emails' => $emails,
                'user_role' => $this->getUserRoleInfo($user),
                'access_level' => $this->determineAccessLevel($user)
            ], 'All emails retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve all emails', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve emails', 500, [
                'error' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Get my emails (only created by logged-in user)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyEmails(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Get base query
            $query = $this->getEmailBaseQuery();

            // Filter by created_by (only user's own emails)
            $query->where('created_by', $user->id);

            // Apply additional filters from request
            $query = $this->applyRequestFilters($query, $request);

            // Order by created_at descending
            $query->orderBy('created_at', 'desc');

            // Paginate results
            $perPage = $request->get('per_page', 15);
            $emails = $query->paginate($perPage);

            return $this->successResponse([
                'emails' => $emails,
                'created_by' => $user->id,
                'user_name' => $user->first_name . ' ' . $user->last_name
            ], 'My emails retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve my emails', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve my emails', 500, [
                'error' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Store a newly created email in storage and send it.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'followup_business_id' => 'required|exists:followup_businesses,id',
            'to' => 'required|array|min:1',
            'to.*' => 'required|email',
            'cc' => 'nullable|array',
            'cc.*' => 'nullable|email',
            'bcc' => 'nullable|array',
            'bcc.*' => 'nullable|email',
            'type' => 'required|string|max:255',
            'template' => 'required|string',
            'dynamic_data' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            // Get business details for dynamic data
            $business = \App\Models\FollowupBusiness::with('authPersons')->find($request->followup_business_id);

            // Prepare dynamic data with business information
            $dynamicData = $request->dynamic_data ?? [];
            $dynamicData['business_name'] = $business->name ?? '';
            $dynamicData['business_email'] = $business->email ?? '';
            $dynamicData['business_phone'] = $business->phone ?? '';
            $dynamicData['business_category'] = $business->category ?? '';
            $dynamicData['business_type'] = $business->type ?? '';
            $dynamicData['business_website'] = $business->website ?? '';

            // Add auth person information if available
            if ($business && $business->authPersons && $business->authPersons->isNotEmpty()) {
                $primaryAuthPerson = $business->authPersons->where('is_primary', true)->first();
                if (!$primaryAuthPerson) {
                    $primaryAuthPerson = $business->authPersons->first();
                }
                if ($primaryAuthPerson) {
                    $dynamicData['contact_name'] = $primaryAuthPerson->firstname . ' ' . $primaryAuthPerson->lastname;
                    $dynamicData['contact_email'] = $primaryAuthPerson->primaryemail ?? '';
                    $dynamicData['contact_phone'] = $primaryAuthPerson->primaryphone ?? '';
                    $dynamicData['contact_mobile'] = $primaryAuthPerson->primarymobile ?? '';
                    $dynamicData['contact_designation'] = $primaryAuthPerson->designation ?? '';
                }
            }

            // Replace placeholders in template with dynamic data
            $emailContent = $request->template;
            foreach ($dynamicData as $key => $value) {
                $emailContent = str_replace('{' . $key . '}', $value, $emailContent);
                $emailContent = str_replace('{{' . $key . '}}', $value, $emailContent);
            }

            // Static subject (can be customized as needed)
            $emailSubject = 'Follow-up Email';

            // Send the email using Laravel's Mail facade
            Mail::raw($emailContent, function ($message) use ($request, $emailSubject) {
                $message->to($request->to)
                    ->cc($request->cc ?? [])
                    ->bcc($request->bcc ?? [])
                    ->subject($emailSubject);

                // Set from address from .env config
                $fromAddress = config('mail.from.address');
                $fromName = config('mail.from.name');
                if ($fromAddress) {
                    $message->from($fromAddress, $fromName);
                }
            });

            // Store email record
            $email = Email::create([
                'followup_business_id' => $request->followup_business_id,
                'to' => $request->to,
                'cc' => $request->cc,
                'bcc' => $request->bcc,
                'type' => $request->type,
                'created_by' => auth()->id(),
            ]);

            $email->load(['followupBusiness:id,name', 'creator:id,first_name,last_name']);

            Log::info('Email sent successfully', [
                'email_id' => $email->id,
                'to' => $request->to,
                'subject' => $emailSubject,
                'created_by' => auth()->id(),
            ]);

            return $this->successResponse($email, 'Email sent successfully', 201);

        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['template']),
            ]);

            return $this->errorResponse('Failed to send email: ' . $e->getMessage(), 500, [
                'error' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Display the specified email.
     */
    public function show(int $id): JsonResponse
    {
        $email = Email::with(['followupBusiness:id,name', 'creator:id,first_name,last_name'])->find($id);

        if (!$email) {
            return $this->errorResponse('Email not found', 404);
        }

        return $this->successResponse($email, 'Email retrieved successfully');
    }

    /**
     * Update the specified email in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $email = Email::find($id);

        if (!$email) {
            return $this->errorResponse('Email not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'followup_business_id' => 'sometimes|required|exists:followup_businesses,id',
            'to' => 'sometimes|required|array|min:1',
            'to.*' => 'required|email',
            'cc' => 'nullable|array',
            'cc.*' => 'nullable|email',
            'bcc' => 'nullable|array',
            'bcc.*' => 'nullable|email',
            'type' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $updateData = [];
        
        if ($request->has('followup_business_id')) {
            $updateData['followup_business_id'] = $request->followup_business_id;
        }
        if ($request->has('to')) {
            $updateData['to'] = $request->to;
        }
        if ($request->has('cc')) {
            $updateData['cc'] = $request->cc;
        }
        if ($request->has('bcc')) {
            $updateData['bcc'] = $request->bcc;
        }
        if ($request->has('type')) {
            $updateData['type'] = $request->type;
        }

        $email->update($updateData);

        $email->load(['followupBusiness:id,name', 'creator:id,first_name,last_name']);

        return $this->successResponse($email, 'Email updated successfully');
    }

    /**
     * Remove the specified email from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $email = Email::find($id);

        if (!$email) {
            return $this->errorResponse('Email not found', 404);
        }

        $email->delete();

        return $this->successResponse(null, 'Email deleted successfully');
    }

    /**
     * Get base query for emails with common eager loads
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getEmailBaseQuery()
    {
        return Email::with([
            'followupBusiness:id,name,category,type,phone,email,website',
            'creator:id,first_name,last_name,user_type'
        ]);
    }

    /**
     * Apply role-based filters to query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyRoleBasedFilters($query, User $user)
    {
        $accessLevel = $this->determineAccessLevel($user);

        switch ($accessLevel) {
            case 'admin':
                // Admin can see all emails - no additional filter needed
                Log::info('Admin accessing all emails', ['user_id' => $user->id]);
                break;

            case 'manager':
                // Manager (Lead Generation dept + has team): See team members' emails + own
                $teamMemberIds = $this->getTeamMemberIds($user);
                $teamMemberIds[] = $user->id; // Include self
                
                Log::info('Manager accessing team emails', [
                    'user_id' => $user->id,
                    'team_member_count' => count($teamMemberIds)
                ]);

                $query->whereIn('created_by', $teamMemberIds);
                break;

            case 'executive':
                // Executive (Lead Generation dept): See only own emails
                Log::info('Executive accessing own emails', ['user_id' => $user->id]);
                $query->where('created_by', $user->id);
                break;

            default:
                // Default: Only own emails for safety
                Log::warning('Unknown user type, defaulting to own emails only', [
                    'user_id' => $user->id,
                    'user_type' => $user->user_type
                ]);
                $query->where('created_by', $user->id);
                break;
        }

        return $query;
    }

    /**
     * Determine access level based on user roles and departments
     *
     * @param User $user
     * @return string
     */
    private function determineAccessLevel(User $user): string
    {
        // Check if user has admin role
        $isAdmin = $user->roles->contains(function ($role) {
            return strtolower($role->name) === 'admin' || 
                   strtolower($role->name) === 'superadmin' ||
                   strtolower($role->name) === 'super_admin';
        });

        if ($isAdmin || strtolower($user->user_type) === 'admin') {
            return 'admin';
        }

        // Check if user is in Lead Generation department
        $isLeadGenerationDept = $user->departments->contains(function ($dept) {
            return strtolower($dept->name) === 'lead generation' ||
                   strtolower($dept->name) === 'lead_generation' ||
                   strtolower($dept->name) === 'leadgeneration';
        });

        // Check if user has manager role
        $isManager = $user->roles->contains(function ($role) {
            return strtolower($role->name) === 'manager' ||
                   strtolower($role->name) === 'team manager' ||
                   strtolower($role->name) === 'team_manager';
        });

        // Check if user belongs to any team
        $hasTeams = $user->teams->isNotEmpty();

        // Manager in Lead Generation with teams
        if ($isManager && $isLeadGenerationDept && $hasTeams) {
            return 'manager';
        }

        // Executive in Lead Generation
        $isExecutive = $user->roles->contains(function ($role) {
            return strtolower($role->name) === 'executive' ||
                   strtolower($role->name) === 'sales executive' ||
                   strtolower($role->name) === 'sales_executive';
        });

        if (($isExecutive || strtolower($user->user_type) === 'executive') && $isLeadGenerationDept) {
            return 'executive';
        }

        // Default fallback - if user has any team membership, treat as manager
        if ($hasTeams) {
            return 'manager';
        }

        return 'executive'; // Default to most restrictive
    }

    /**
     * Get team member IDs for a user (users in the same teams)
     *
     * @param User $user
     * @return array
     */
    private function getTeamMemberIds(User $user): array
    {
        $teamIds = $user->teams->pluck('id')->toArray();

        if (empty($teamIds)) {
            return [];
        }

        // Get all users who belong to any of the same teams
        $teamMemberIds = DB::table('team_user')
            ->whereIn('team_id', $teamIds)
            ->where('user_id', '!=', $user->id) // Exclude current user
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        return $teamMemberIds;
    }

    /**
     * Apply additional filters from request
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param Request $request
     * @param array $excludeFilters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function applyRequestFilters($query, Request $request, array $excludeFilters = [])
    {
        // Type filter
        if ($request->has('type') && !in_array('type', $excludeFilters)) {
            $query->where('type', $request->type);
        }

        // Followup business filter
        if ($request->has('followup_business_id') && !in_array('followup_business_id', $excludeFilters)) {
            $query->where('followup_business_id', $request->followup_business_id);
        }

        // Date range filters
        if ($request->has('date_from') && !in_array('date_from', $excludeFilters)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !in_array('date_to', $excludeFilters)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search by business name
        if ($request->has('search') && !in_array('search', $excludeFilters)) {
            $search = $request->search;
            $query->whereHas('followupBusiness', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        return $query;
    }

    /**
     * Get user role info for response
     *
     * @param User $user
     * @return array
     */
    private function getUserRoleInfo(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'user_type' => $user->user_type,
            'roles' => $user->roles->pluck('name')->toArray(),
            'departments' => $user->departments->pluck('name')->toArray(),
            'teams' => $user->teams->pluck('name')->toArray()
        ];
    }
}
