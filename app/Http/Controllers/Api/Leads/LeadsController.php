<?php

namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Concerns\AppliesLastThreeMonthsFilter;
use App\Models\FollowupBusiness;
use App\Models\FollowupAuthPerson;
use App\Models\BusinessService;
use App\Models\LeadQualification;
use App\Models\Comment;
use App\Models\User;
use App\Services\DateRangeFilterService;
use App\Support\FollowupBusinessProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeadsController extends BaseApiController
{
    use AppliesLastThreeMonthsFilter;

    public function __construct(
        private readonly DateRangeFilterService $dateRangeFilterService
    ) {
    }

    /**
     * Create lead with business, auth persons, and comments
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->leadPayloadValidationRules(isUpdate: false));

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $business = FollowupBusiness::create(array_merge(
                $this->businessAttributesFromRequest($request),
                ['created_by' => auth()->id()]
            ));

            // Create auth persons and attach to business
            $authPersonIds = [];
            foreach ($request->auth_persons as $authPersonData) {
                $authPerson = FollowupAuthPerson::create(
                    $this->authPersonAttributesFromRequest($authPersonData, true)
                );

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

            // Create business service profile if provided
            if ($request->has('business_service') && is_array($request->business_service)
                && BusinessService::hasPayloadData($request->business_service)) {
                BusinessService::createForBusiness($business->id, $request->business_service);
            }

            // Create lead qualification profile if provided
            if ($request->has('lead_qualification') && is_array($request->lead_qualification)
                && LeadQualification::hasPayloadData($request->lead_qualification)) {
                LeadQualification::createForBusiness($business->id, $request->lead_qualification);
            }

            return $this->successResponse(
                $this->leadResponsePayload($business),
                'Lead created successfully',
                201
            );
        }, 'Lead creation', $request->only(['business_name']));
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
        $query = $this->applyLastThreeMonthsFilter($query, 'followup_businesses.created_at');
        $query = $this->applyRequestFilters($query, $request);
        $query->orderByDesc('followup_businesses.created_at')->orderByDesc('followup_businesses.id');

        $leads = $query->get();

        return $this->successResponse([
            'leads' => $leads,
            'total' => $leads->count(),
            ...$this->lastThreeMonthsDateRange(),
            'user_role' => $this->getUserRoleInfo($user),
            'access_level' => $this->determineAccessLevel($user),
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
        $query = $this->applyLastThreeMonthsFilter($query, 'followup_businesses.created_at');
        $query = $this->applyRequestFilters($query, $request);
        $query->orderByDesc('followup_businesses.created_at')->orderByDesc('followup_businesses.id');

        $leads = $query->get();

        return $this->successResponse([
            'leads' => $leads,
            'total' => $leads->count(),
            ...$this->lastThreeMonthsDateRange(),
            'created_by' => $user->id,
            'user_name' => $user->first_name . ' ' . $user->last_name,
        ], 'My leads retrieved successfully');
    }

    /**
     * Get all business names and IDs
     */
    public function getAllBusinessNames(): JsonResponse
    {
        $businesses = FollowupBusiness::select('id', 'name')
            ->orderBy('name', 'asc')
            ->get();

        return $this->successResponse($businesses, 'Business names retrieved successfully');
    }

    /**
     * Display a listing of leads (legacy)
     */
    public function index(Request $request): JsonResponse
    {
        return $this->getAllLeads($request);
    }

    /**
     * Filter leads with flexible date, user, search, and field filters.
     */
    public function filterLeads(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $user = auth()->user();
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $user->load(['roles', 'teams', 'departments']);

            $query = FollowupBusiness::with([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails',
                'comments' => function ($commentQuery) {
                    $commentQuery->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                },
            ]);

            $scope = $request->input('scope', 'all');
            if ($scope === 'my') {
                $query->where('created_by', $user->id);
            } else {
                $query = $this->applyRoleBasedFilters($query, $user);
            }

            $query = $this->dateRangeFilterService->applyFilters($query, $request, [
                'date_column' => $request->input('date_column', 'created_at'),
                'user_column' => 'created_by',
                'search_columns' => ['name', 'trading_name', 'company_registration_number', 'address_line1', 'city', 'postcode', 'country', 'category', 'sub_category', 'type', 'source_name', 'sub_source'],
                'skip_status_filter' => true,
            ]);

            if ($request->has('category')) {
                $query->where('category', $request->input('category'));
            }

            if ($request->has('type')) {
                $query->where('type', $request->input('type'));
            }

            if ($request->has('source_name')) {
                $query->where('source_name', $request->input('source_name'));
            }

            if ($request->has('sub_source')) {
                $query->where('sub_source', $request->input('sub_source'));
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->input('priority'));
            }

            if ($request->has('status')) {
                $query->whereHas('followupDetails', function ($statusQuery) use ($request) {
                    $statusQuery->where('status', $request->input('status'));
                });
            }

            $perPage = $request->input('per_page', 15);
            $leads = $query->orderByDesc('followup_businesses.created_at')
                ->orderByDesc('followup_businesses.id')
                ->cursorPaginate($perPage);

            return $this->successResponse($leads, 'Leads retrieved successfully');
        }, 'Leads filter retrieval');
    }

    /**
     * Get filter options for leads.
     */
    public function getFilterOptions(): JsonResponse
    {
        $sourceNames = FollowupBusiness::query()
            ->whereNotNull('source_name')
            ->where('source_name', '!=', '')
            ->distinct()
            ->orderBy('source_name')
            ->pluck('source_name')
            ->values()
            ->toArray();

        $filterOptions = [
            'date_filters' => DateRangeFilterService::getDateFilterOptions(),
            'date_columns' => DateRangeFilterService::getDateColumns('leads'),
            'scope_options' => [
                'all' => 'All leads (role-based access)',
                'my' => 'My leads only',
            ],
            'category_options' => [
                'Technology Services',
                'Healthcare',
                'Finance',
                'Education',
                'Retail',
                'Manufacturing',
                'Other',
            ],
            'type_options' => [
                'Enterprise Client',
                'SME',
                'Startup',
                'Individual',
                'Government',
                'Non-Profit',
            ],
            'source_name_options' => $sourceNames,
            'priority_options' => FollowupBusiness::PRIORITIES,
            'status_options' => [
                'New',
                'Contacted',
                'Interested',
                'Not Interested',
                'Follow-up Scheduled',
                'Appointment Booked',
                'Converted',
                'Lost',
            ],
        ];

        return $this->successResponse($filterOptions, 'Filter options retrieved successfully');
    }

    /**
     * Display the specified lead with all related data.
     */
    public function show(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $business = FollowupBusiness::find($id);

            if (!$business) {
                return $this->errorResponse('Lead not found', 404);
            }

            return $this->successResponse(
                $this->leadResponsePayload($business),
                'Lead retrieved successfully'
            );
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

        $validator = Validator::make($request->all(), $this->leadPayloadValidationRules(isUpdate: true));

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $business) {
            $updateData = $this->businessAttributesFromRequest($request, onlyProvided: true);

            if ($updateData !== []) {
                $business->update($updateData);
            }

            if ($request->has('auth_persons') && is_array($request->auth_persons)) {
                $this->syncAuthPersonsForLead($business, $request->auth_persons);
            }

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

            if ($request->has('business_service') && is_array($request->business_service)
                && BusinessService::hasPayloadData($request->business_service)) {
                BusinessService::upsertForBusiness($business->id, $request->business_service);
            }

            if ($request->has('lead_qualification') && is_array($request->lead_qualification)
                && LeadQualification::hasPayloadData($request->lead_qualification)) {
                LeadQualification::upsertForBusiness($business->id, $request->lead_qualification);
            }

            return $this->successResponse(
                $this->leadResponsePayload($business->fresh()),
                'Lead updated successfully'
            );
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

    /**
     * Shared validation for create and update lead payloads.
     *
     * @return array<string, string>
     */
    private function leadPayloadValidationRules(bool $isUpdate = false): array
    {
        $rules = [
            'business_name' => ($isUpdate ? 'sometimes|' : '').'required|string|max:255',
            'trading_name' => 'nullable|string|max:255',
            'company_registration_number' => 'nullable|string|max:100',
            'address_line1' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:50',
            'postcode' => 'nullable|string|max:50',
            'country' => 'nullable|string|max:50',
            'company_size' => 'nullable|string|max:100',
            'company_type' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:255',
            'sub_category' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'source_name' => 'nullable|string|max:50',
            'sub_source' => 'nullable|string|max:50',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'annual_revenue' => 'nullable|numeric|min:0',
            'number_of_locations' => 'nullable|integer|min:0',
            'website' => 'nullable|url|max:255',
            'auth_persons' => ($isUpdate ? 'nullable' : 'required').'|array',
            'auth_persons.*.title' => 'nullable|string|max:50',
            'auth_persons.*.firstname' => 'required|string|max:255',
            'auth_persons.*.middlename' => 'nullable|string|max:255',
            'auth_persons.*.lastname' => 'required|string|max:255',
            'auth_persons.*.is_primary' => 'nullable|boolean',
            'auth_persons.*.job_title' => 'nullable|string|max:255',
            'auth_persons.*.gender' => 'nullable|in:male,female,other',
            'auth_persons.*.dob' => 'nullable|date',
            'auth_persons.*.primaryphone' => 'nullable|string',
            'auth_persons.*.altphone' => 'nullable|string',
            'auth_persons.*.primarymobile' => 'nullable|string',
            'auth_persons.*.altmobile' => 'nullable|string',
            'auth_persons.*.primaryemail' => 'required|email',
            'auth_persons.*.altemail' => 'nullable|email',
            'comments' => 'nullable|array',
            'comments.*.followup_business_id' => 'nullable|exists:followup_businesses,id',
            'comments.*.comment' => 'required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ];

        if ($isUpdate) {
            $rules['auth_persons.*.id'] = 'nullable|exists:followup_auth_persons,id';
        }

        return $rules
            + FollowupAuthPerson::profileFieldValidationRules('auth_persons.*')
            + BusinessService::validationRules()
            + LeadQualification::validationRules();
    }

    /**
     * @return array<string, mixed>
     */
    private function businessAttributesFromRequest(Request $request, bool $onlyProvided = false): array
    {
        $fieldMap = [
            'business_name' => 'name',
            'trading_name' => 'trading_name',
            'company_registration_number' => 'company_registration_number',
            'address_line1' => 'address_line1',
            'city' => 'city',
            'postcode' => 'postcode',
            'country' => 'country',
            'company_size' => 'company_size',
            'company_type' => 'company_type',
            'category' => 'category',
            'sub_category' => 'sub_category',
            'type' => 'type',
            'source_name' => 'source_name',
            'sub_source' => 'sub_source',
            'priority' => 'priority',
            'annual_revenue' => 'annual_revenue',
            'number_of_locations' => 'number_of_locations',
            'website' => 'website',
        ];

        $attributes = [];

        foreach ($fieldMap as $requestKey => $column) {
            if ($onlyProvided && !$request->has($requestKey)) {
                continue;
            }

            $attributes[$column] = $request->input($requestKey);
        }

        return $attributes;
    }

    /**
     * @param  array<int, array<string, mixed>>  $authPersonsData
     */
    private function syncAuthPersonsForLead(FollowupBusiness $business, array $authPersonsData): void
    {
        $currentAuthPersonIds = $business->authPersons()->pluck('followup_auth_persons.id')->all();
        $newAuthPersonIds = [];

        foreach ($authPersonsData as $authPersonData) {
            if (!empty($authPersonData['id'])) {
                $authPerson = FollowupAuthPerson::find($authPersonData['id']);
                if ($authPerson === null || !$business->authPersons()->where('followup_auth_persons.id', $authPerson->id)->exists()) {
                    continue;
                }

                $authPerson->update($this->authPersonAttributesFromRequest($authPersonData));
                $newAuthPersonIds[] = $authPerson->id;

                continue;
            }

            $authPerson = FollowupAuthPerson::create($this->authPersonAttributesFromRequest($authPersonData, true));
            $newAuthPersonIds[] = $authPerson->id;
        }

        $idsToDetach = array_diff($currentAuthPersonIds, $newAuthPersonIds);
        foreach ($idsToDetach as $authPersonId) {
            $business->authPersons()->detach($authPersonId);

            $personToDelete = FollowupAuthPerson::find($authPersonId);
            if ($personToDelete !== null && $personToDelete->businesses()->count() === 0) {
                $personToDelete->delete();
            }
        }

        $business->authPersons()->sync($newAuthPersonIds);
    }

    /**
     * @return array<string, mixed>
     */
    private function authPersonAttributesFromRequest(array $authPersonData, bool $isNew = false): array
    {
        $attributes = array_merge([
            'title' => $authPersonData['title'] ?? null,
            'firstname' => $authPersonData['firstname'],
            'middlename' => $authPersonData['middlename'] ?? null,
            'lastname' => $authPersonData['lastname'],
            'is_primary' => $authPersonData['is_primary'] ?? false,
            'job_title' => $authPersonData['job_title'] ?? null,
            'gender' => $authPersonData['gender'] ?? null,
            'dob' => $authPersonData['dob'] ?? null,
            'primaryphone' => $authPersonData['primaryphone'] ?? null,
            'altphone' => $authPersonData['altphone'] ?? null,
            'primarymobile' => $authPersonData['primarymobile'] ?? null,
            'altmobile' => $authPersonData['altmobile'] ?? null,
            'primaryemail' => $authPersonData['primaryemail'],
            'altemail' => $authPersonData['altemail'] ?? null,
        ], FollowupAuthPerson::profileFieldsFromArray($authPersonData));

        if ($isNew) {
            $attributes['created_by'] = auth()->id();
        }

        return $attributes;
    }

    /**
     * @return array<string, mixed>
     */
    private function leadResponsePayload(FollowupBusiness $business): array
    {
        $business->load($this->leadDetailRelations());

        return FollowupBusinessProfile::leadShowPayload($business, [
            'comments' => $business->comments,
            'followup_details' => $business->followupDetails,
            'emails' => $business->emails,
            'appointments' => $business->appointments,
            'deals' => $business->deals,
            'seo_details' => $business->seoDetails,
        ]);
    }

    /**
     * @return array<int|string, mixed>
     */
    private function leadDetailRelations(): array
    {
        return array_merge(FollowupBusiness::profileRelations(), [
            'comments' => function ($query) {
                $query->with('creator:id,first_name,last_name')->orderByDesc('created_at');
            },
            'followupDetails' => function ($query) {
                $query->with('creator:id,first_name,last_name')
                    ->orderByDesc('date')
                    ->orderByDesc('time');
            },
            'emails' => function ($query) {
                $query->with('creator:id,first_name,last_name')->orderByDesc('created_at');
            },
            'appointments' => function ($query) {
                $query->with([
                    'timeSlot:id,name,start_time,end_time,duration_minutes',
                    'creator:id,first_name,last_name',
                    'quality',
                    'consultations' => function ($consultationQuery) {
                        $consultationQuery->with([
                            'meetingSlot:id,start_time,end_time',
                            'assignedUser:id,first_name,last_name,username',
                        ])->orderByDesc('created_at');
                    },
                ])->orderByDesc('date')->orderByDesc('time_slot_id');
            },
            'deals' => function ($query) {
                $query->with([
                    'authPerson:id,title,firstname,lastname,primaryemail,primarymobile',
                    'creator:id,first_name,last_name',
                ])->orderByDesc('created_at');
            },
            'seoDetails' => function ($query) {
                $query->with('assignedUser:id,first_name,last_name,username')
                    ->orderByDesc('created_at');
            },
        ]);
    }

    private function getLeadsBaseQuery()
    {
        return FollowupBusiness::with(['creator:id,first_name,last_name', 'authPersons:id,title,firstname,lastname,job_title,primaryemail,primarymobile,is_primary', 'comments']);
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
        if ($request->has('source_name')) $query->where('source_name', $request->source_name);
        if ($request->has('sub_source')) $query->where('sub_source', $request->sub_source);
        if ($request->has('priority')) $query->where('priority', $request->priority);
        if ($request->has('name')) $query->where('name', 'like', '%' . $request->name . '%');
        return $query;
    }

    private function getUserRoleInfo(User $user): array
    {
        return ['id' => $user->id, 'name' => $user->first_name . ' ' . $user->last_name, 'user_type' => $user->user_type];
    }

    /**
     * Check for duplicate lead data (business name, website, phone, mobile, email).
     */
    public function checkDuplicate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string',
            'mobile' => 'nullable|string',
            'email' => 'nullable|email',
            'auth_person_phone' => 'nullable|string',
            'auth_person_mobile' => 'nullable|string',
            'auth_person_email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $businessName = $this->filledDuplicateValue($request, 'business_name');
        $website = $this->filledDuplicateValue($request, 'website');
        $phone = $this->filledDuplicateValue($request, 'phone')
            ?? $this->filledDuplicateValue($request, 'auth_person_phone');
        $mobile = $this->filledDuplicateValue($request, 'mobile')
            ?? $this->filledDuplicateValue($request, 'auth_person_mobile');
        $email = $this->filledDuplicateValue($request, 'email')
            ?? $this->filledDuplicateValue($request, 'auth_person_email');

        if ($businessName === null && $website === null && $phone === null && $mobile === null && $email === null) {
            return $this->errorResponse('Validation failed', 422, [
                'fields' => ['At least one of business_name, website, phone, mobile, or email is required.'],
            ]);
        }

        $duplicates = [];

        if ($businessName !== null) {
            $normalizedName = $this->normalizeBusinessName($businessName);
            $business = FollowupBusiness::query()
                ->where(function ($query) use ($normalizedName) {
                    $query->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
                        ->orWhereRaw('LOWER(TRIM(trading_name)) = ?', [$normalizedName]);
                })
                ->first();

            if ($business !== null) {
                $duplicates['business_name'] = $this->duplicateResultForBusiness($business);
            }
        }

        if ($website !== null) {
            $normalizedWebsite = $this->normalizeWebsite($website);
            $business = FollowupBusiness::query()
                ->whereNotNull('website')
                ->where('website', '!=', '')
                ->get()
                ->first(fn (FollowupBusiness $candidate) => $this->normalizeWebsite($candidate->website) === $normalizedWebsite);

            if ($business !== null) {
                $duplicates['website'] = $this->duplicateResultForBusiness($business, includeWebsite: true);
            }
        }

        if ($phone !== null) {
            $authPerson = $this->findAuthPersonByContact('phone', $phone);
            if ($authPerson !== null) {
                $duplicates['phone'] = $this->duplicateResultForAuthPerson($authPerson);
            }
        }

        if ($mobile !== null) {
            $authPerson = $this->findAuthPersonByContact('mobile', $mobile);
            if ($authPerson !== null) {
                $duplicates['mobile'] = $this->duplicateResultForAuthPerson($authPerson);
            }
        }

        if ($email !== null) {
            $authPerson = $this->findAuthPersonByContact('email', strtolower($email));
            if ($authPerson !== null) {
                $duplicates['email'] = $this->duplicateResultForAuthPerson($authPerson);
            }
        }

        $hasDuplicates = $duplicates !== [];

        return $this->successResponse([
            'has_duplicates' => $hasDuplicates,
            'duplicates' => $duplicates,
        ], $hasDuplicates ? 'Duplicates found' : 'No duplicates found');
    }

    private function filledDuplicateValue(Request $request, string $key): ?string
    {
        if (!$request->has($key)) {
            return null;
        }

        $value = trim((string) $request->input($key));

        return $value === '' ? null : $value;
    }

    private function normalizeWebsite(?string $website): ?string
    {
        if ($website === null) {
            return null;
        }

        $normalized = strtolower(rtrim(trim($website), '/'));

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeBusinessName(string $businessName): string
    {
        return strtolower(trim($businessName));
    }

    private function findAuthPersonByContact(string $type, string $value): ?FollowupAuthPerson
    {
        return FollowupAuthPerson::query()
            ->where(function ($query) use ($type, $value) {
                if ($type === 'phone') {
                    $query->where('primaryphone', $value)
                        ->orWhere('altphone', $value);

                    return;
                }

                if ($type === 'mobile') {
                    $query->where('primarymobile', $value)
                        ->orWhere('altmobile', $value);

                    return;
                }

                $query->whereRaw('LOWER(primaryemail) = ?', [$value])
                    ->orWhereRaw('LOWER(altemail) = ?', [$value]);
            })
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function duplicateResultForBusiness(FollowupBusiness $business, bool $includeWebsite = false): array
    {
        $result = [
            'exists' => true,
            'lead_id' => $business->id,
            'business_name' => $business->name,
        ];

        if ($includeWebsite) {
            $result['website'] = $business->website;
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function duplicateResultForAuthPerson(FollowupAuthPerson $authPerson): array
    {
        $business = $authPerson->businesses()->first();

        return [
            'exists' => true,
            'lead_id' => $business?->id,
            'business_name' => $business?->name,
            'auth_person_name' => trim($authPerson->firstname.' '.$authPerson->lastname),
        ];
    }
}
