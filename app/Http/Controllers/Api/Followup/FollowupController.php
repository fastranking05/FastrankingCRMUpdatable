<?php

namespace App\Http\Controllers\Api\Followup;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Concerns\AppliesLastThreeMonthsFilter;
use App\Models\FollowupBusiness;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupDetail;
use App\Models\Comment;
use App\Models\Appointment;
use App\Services\QualityAssignmentService;
use App\Services\DateRangeFilterService;
use App\Support\FollowupBusinessProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FollowupController extends BaseApiController
{
    use AppliesLastThreeMonthsFilter;

    protected $qualityAssignmentService;
    protected $dateRangeFilterService;

    public function __construct(
        QualityAssignmentService $qualityAssignmentService,
        DateRangeFilterService $dateRangeFilterService
    ) {
        $this->qualityAssignmentService = $qualityAssignmentService;
        $this->dateRangeFilterService = $dateRangeFilterService;
    }

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

            // Apply flexible filters using DateRangeFilterService
            $query = $this->dateRangeFilterService->applyFilters($query, $request, [
                'date_column' => 'created_at',
                'user_column' => 'created_by',
                'search_columns' => ['name', 'trading_name', 'company_registration_number', 'address', 'category', 'sub_category', 'type', 'source_name', 'sub_source'],
                'skip_status_filter' => true,
            ]);

            // Apply additional specific filters
            if ($request->has('category')) {
                $query->where('category', $request->input('category'));
            }

            // Filter by status (from followup_details)
            if ($request->has('status')) {
                $query->whereHas('followupDetails', function ($q) use ($request) {
                    $q->where('status', $request->input('status'));
                });
            }

            // Pagination (cursor requires stable ordering)
            $perPage = $request->input('per_page', 15);
            $followups = $query->orderByDesc('followup_businesses.created_at')
                ->orderByDesc('followup_businesses.id')
                ->cursorPaginate($perPage);

            return $this->successResponse($followups, 'Follow-up records retrieved successfully');
        }, 'Follow-up list retrieval');
    }

    /**
     * Get filter options for follow-up records
     */
    public function getFilterOptions(): JsonResponse
    {
        $filterOptions = [
            'date_filters' => DateRangeFilterService::getDateFilterOptions(),
            'date_columns' => DateRangeFilterService::getDateColumns('followup'),
            'category_options' => [
                'Technology Services',
                'Healthcare',
                'Finance',
                'Education',
                'Retail',
                'Manufacturing',
                'Other'
            ],
            'type_options' => [
                'Enterprise Client',
                'SME',
                'Startup',
                'Individual',
                'Government',
                'Non-Profit'
            ],
            'status_options' => [
                'New',
                'Contacted',
                'Interested',
                'Not Interested',
                'Follow-up Scheduled',
                'Appointment Booked',
                'Converted',
                'Lost'
            ]
        ];

        return $this->successResponse($filterOptions, 'Filter options retrieved successfully');
    }

    /**
     * Store follow-up details and comments for an existing business.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Follow-up Business ID (required - user must provide manually)
            'followup_business_id' => 'required|exists:followup_businesses,id',
            
            // Follow-up Details (array)
            'followup_details' => 'nullable|array',
            'followup_details.*.status' => 'nullable|string|max:255',
            'followup_details.*.date' => 'nullable|date',
            'followup_details.*.time' => 'nullable|date_format:H:i',
            
            // Comments (array) - directly linked to business
            'comments' => 'nullable|array',
            'comments.*.comment' => 'required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            // Get existing business
            $business = FollowupBusiness::find($request->followup_business_id);
            
            if (!$business) {
                return $this->errorResponse('Business not found', 404);
            }

            // Create Follow-up Details if provided
            $followupDetails = [];
            if ($request->has('followup_details')) {
                foreach ($request->followup_details as $detailData) {
                    $detailData['followup_business_id'] = $business->id;
                    $detailData['created_by'] = auth()->id();
                    
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

            // Load complete relationship data
            $business->load([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails',
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ]);

            return $this->successResponse($business, 'Follow-up details and comments created successfully', 201);
        }, 'Follow-up creation', ['followup_business_id' => $request->followup_business_id]);
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

            $followup->load($this->followupDetailRelations());

            return $this->successResponse(
                FollowupBusinessProfile::leadShowPayload($followup, [
                    'followup_details' => $followup->followupDetails,
                    'comments' => $followup->comments,
                ]),
                'Follow-up record retrieved successfully'
            );
        }, 'Follow-up retrieval', ['followup_id' => $id]);
    }

    /**
     * Update follow-up details and comments for an existing business.
     */
    public function update(Request $request, $id): JsonResponse
    {
        // Handle both integer and string IDs
        $followup = is_numeric($id) ? FollowupBusiness::find($id) : FollowupBusiness::find($id);

        if (!$followup) {
            return $this->errorResponse('Follow-up record not found', 404);
        }

        $validator = Validator::make($request->all(), [
            // Follow-up Details (array)
            'followup_details' => 'nullable|array',
            'followup_details.*.status' => 'nullable|string|max:255',
            'followup_details.*.date' => 'nullable|date',
            'followup_details.*.time' => 'nullable|date_format:H:i',
            
            // Comments (array) - directly linked to business
            'comments' => 'nullable|array',
            'comments.*.comment' => 'sometimes|required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $followup) {
            // Create Follow-up Details if provided
            if ($request->has('followup_details')) {
                foreach ($request->followup_details as $detailData) {
                    $newDetailData = $detailData;
                    $newDetailData['followup_business_id'] = $followup->id;
                    $newDetailData['created_by'] = auth()->id();
                    
                    // Remove ID if provided to ensure new record creation
                    unset($newDetailData['id']);
                    
                    FollowupDetail::create($newDetailData);
                }
            }

            // Create Comments if provided
            if ($request->has('comments')) {
                foreach ($request->comments as $commentData) {
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

            $followup->load($this->followupDetailRelations());

            return $this->successResponse($followup, 'Follow-up details and comments updated successfully');
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

            // Build the base query - only include businesses that have followup details
            $query = FollowupBusiness::with([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails' => function ($query) {
                    $query->latest('date')->latest('time')->limit(1);
                },
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ])->whereHas('followupDetails');

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

            $this->applyFollowupBusinessCursorOrdering($query);

            $followups = $query->get();

            return $this->successResponse([
                'followups' => $followups,
                'total' => $followups->count(),
                'access_info' => [
                    'access_level' => $accessLevel,
                    'user_roles' => $roleNames,
                    'user_departments' => $departmentNames,
                    'is_admin' => $isAdmin,
                    'is_manager_lead_gen' => $isManager && $isLeadGenerationDept,
                    'is_executive_lead_gen' => $isExecutive && $isLeadGenerationDept,
                    'team_count' => count($teamIds),
                ],
            ], 'Follow-up records retrieved successfully');
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
            ])->where('created_by', auth()->id())
              ->whereHas('followupDetails');

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

            $this->applyFollowupBusinessCursorOrdering($query);

            $followups = $query->get();

            return $this->successResponse([
                'followups' => $followups,
                'total' => $followups->count(),
            ], 'My follow-ups retrieved successfully');
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

            $this->applyFollowupBusinessCursorOrdering($query);

            $perPage = $request->get('per_page', 15);
            $followups = $query->cursorPaginate($perPage);

            return $this->successResponse($followups, 'Today\'s follow-ups retrieved successfully');
        }, 'Today\'s follow-ups retrieval');
    }

    /**
     * @return array<int|string, mixed>
     */
    private function followupDetailRelations(): array
    {
        return array_merge(FollowupBusiness::profileRelations(), [
            'followupDetails' => function ($query) {
                $query->with('creator:id,first_name,last_name')
                    ->orderByDesc('date')
                    ->orderByDesc('time');
            },
            'comments' => function ($query) {
                $query->with('creator:id,first_name,last_name')->orderByDesc('created_at');
            },
        ]);
    }

    /**
     * CursorPaginator requires ORDER BY scalar columns only (see migration + FollowupBusiness::refreshLatestFollowupSortFromDetails).
     */
    private function applyFollowupBusinessCursorOrdering($query): void
    {
        $query->orderByDesc('followup_businesses.latest_followup_date')
            ->orderByDesc('followup_businesses.latest_followup_time')
            ->orderByDesc('followup_businesses.created_at')
            ->orderByDesc('followup_businesses.id');
    }
}
