<?php

namespace App\Http\Controllers\Api\Seo;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\SeoDetail;
use App\Models\User;
use App\Services\DateRangeFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeoFilterController extends BaseApiController
{
    protected $dateRangeFilterService;

    public function __construct(DateRangeFilterService $dateRangeFilterService = null)
    {
        $this->dateRangeFilterService = $dateRangeFilterService ?: app(DateRangeFilterService::class);
    }

    /**
     * Get filtered SEO records
     * Similar to appointment filtering but for SEO data
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            // Apply role-based filtering first
            $user = auth()->user();
            $query = SeoDetail::with([
                'assignedUser:id,first_name,last_name,email',
                'questionAnswers.question:id,name',
                'followupBusiness:id,name,category,type,website',
                'followupBusiness.authPersons:id,title,firstname,lastname,job_title,primaryemail,primarymobile'
            ]);

            // Apply role-based filtering
            $this->applyRoleBasedFiltering($query, $user);

            // Apply flexible filters using DateRangeFilterService
            $query = $this->dateRangeFilterService->applyFilters($query, $request, [
                'date_column' => $request->input('date_column', 'created_at'), // Default to SEO created_at
                'user_column' => 'assigned_user',
                'status_column' => 'status',
                'search_columns' => ['id', 'reason', 'auditor'] // Search in SEO specific columns
            ]);

            // Apply additional specific filters
            if ($request->has('status')) {
                $query->where('status', $request->input('status'));
            }

            // Apply multiple status filter
            if ($request->has('statuses') && is_array($request->input('statuses'))) {
                $query->whereIn('status', $request->input('statuses'));
            }

            // Filter by assigned user
            if ($request->has('assigned_user_id')) {
                $query->where('assigned_user', $request->input('assigned_user_id'));
            }

            // Filter by business
            if ($request->has('business_id')) {
                $query->where('followup_business_id', $request->input('business_id'));
            }

            // Filter by business name
            if ($request->has('business_name')) {
                $query->whereHas('followupBusiness', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->input('business_name') . '%');
                });
            }

            // Filter by auditor
            if ($request->has('auditor')) {
                $query->where('auditor', 'like', '%' . $request->input('auditor') . '%');
            }

            // Filter by audit date range
            if ($request->has('audit_date_from')) {
                $query->whereDate('audited_date', '>=', $request->input('audit_date_from'));
            }
            if ($request->has('audit_date_to')) {
                $query->whereDate('audited_date', '<=', $request->input('audit_date_to'));
            }

            // Filter by audited website
            if ($request->has('audited_website')) {
                $query->where('audited_website', 'like', '%' . $request->input('audited_website') . '%');
            }

            $seoRecords = $query->orderByDesc('seo_details.updated_at')
                ->orderByDesc('seo_details.id')
                ->cursorPaginate($request->input('per_page', 50));

            return $this->successResponse($seoRecords, 'SEO records retrieved successfully');
        }, 'SEO filtering');
    }

    /**
     * Get filter options for SEO records
     */
    public function getFilterOptions(): JsonResponse
    {
        $filterOptions = [
            'date_filters' => DateRangeFilterService::getDateFilterOptions(),
            'date_columns' => [
                'created_at' => 'SEO Created Date',
                'updated_at' => 'SEO Updated Date',
                'audited_date' => 'Audit Date'
            ],
            'status_options' => [
                'Pending',
                'Audit Completed',
                'Not Applicable',
                'In Progress',
                'On Hold',
                'Cancelled'
            ],
            'assigned_user_options' => function () {
                // Get Digital Marketing users for dropdown
                $digitalMarketingUsers = User::whereHas('departments', function ($query) {
                    $query->where('name', 'Digital Marketing');
                })->where('status', 'active')
                  ->select('id', 'first_name', 'last_name', 'email')
                  ->orderBy('first_name', 'asc')
                  ->get();

                return $digitalMarketingUsers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                        'email' => $user->email
                    ];
                })->toArray();
            },
            'business_options' => function () {
                // Get businesses with SEO records
                return SeoDetail::with('followupBusiness:id,name')
                    ->get()
                    ->pluck('followupBusiness')
                    ->unique('id')
                    ->values()
                    ->map(function ($business) {
                        return [
                            'id' => $business->id,
                            'name' => $business->name
                        ];
                    })->toArray();
            },
            'auditor_options' => function () {
                // Get unique auditors from SEO records
                return SeoDetail::whereNotNull('auditor')
                    ->select('auditor')
                    ->distinct()
                    ->orderBy('auditor', 'asc')
                    ->pluck('auditor')
                    ->filter()
                    ->values()
                    ->toArray();
            }
        ];

        return $this->successResponse($filterOptions, 'SEO filter options retrieved successfully');
    }

    /**
     * Apply role-based filtering to SEO data queries
     */
    private function applyRoleBasedFiltering($query, $user): void
    {
        // Skip role-based filtering if user is null (for testing)
        if (!$user) {
            return;
        }

        // Load user relationships if not already loaded
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }
        if (!$user->relationLoaded('departments')) {
            $user->load('departments');
        }

        // Get user's primary role and department
        $role = $user->roles->first();
        $department = $user->departments->first();

        if ($role && $role->name === 'Admin') {
            // Admin can see all data - no filtering needed
            return;
        }

        if ($role && $department && $role->name === 'Manager' && $department->name === 'Digital Marketing') {
            // Manager can see own + team members' data
            $teamMemberIds = $this->getTeamMemberIds($user);
            $query->whereIn('assigned_user', $teamMemberIds);
        } elseif ($role && $department && $role->name === 'Executive' && $department->name === 'Digital Marketing') {
            // Executive can see only own data
            $query->where('assigned_user', $user->id);
        } else {
            // Other roles can't see any data
            $query->where('assigned_user', 0);
        }
    }

    /**
     * Get team member IDs for a manager
     */
    private function getTeamMemberIds($user): array
    {
        // Get team members from the user's team
        $teamMembers = DB::table('team_user')
            ->join('teams', 'teams.id', '=', 'team_user.team_id')
            ->join('users', 'users.id', '=', 'team_user.user_id')
            ->where('teams.team_leader_id', $user->id)
            ->pluck('users.id')
            ->toArray();

        // Include the manager's own ID
        $teamMemberIds[] = $user->id;

        return array_unique($teamMemberIds);
    }
}
