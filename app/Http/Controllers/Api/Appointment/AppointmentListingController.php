<?php

namespace App\Http\Controllers\Api\Appointment;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Concerns\AppliesLastThreeMonthsFilter;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AppointmentListingController extends BaseApiController
{
    use AppliesLastThreeMonthsFilter;

    /**
     * Get all appointments with role-based hierarchy access
     * 
     * Hierarchy:
     * 1. Admin: Can see all appointments
     * 2. Manager (Lead Generation dept + has team): Can see team members' appointments + own
     * 3. Executive (Lead Generation dept): Can see only own appointments
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllAppointments(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Load user relationships
            $user->load(['roles', 'teams', 'departments']);

            // Get base query with eager loaded relationships
            $query = $this->getAppointmentBaseQuery();

            // Apply role-based filters
            $query = $this->applyRoleBasedFilters($query, $user);

            // Apply additional filters from request
            $query = $this->applyRequestFilters($query, $request);
            $query = $this->applyLastThreeMonthsFilter($query, 'appointments.created_at');

            $query->orderByDesc('appointments.date')
                  ->orderByDesc('appointments.created_at')
                  ->orderByDesc('appointments.id');

            $appointments = $query->get();

            return $this->successResponse([
                'appointments' => $appointments,
                'total' => $appointments->count(),
                ...$this->lastThreeMonthsDateRange(),
                'user_role' => $this->getUserRoleInfo($user),
                'access_level' => $this->determineAccessLevel($user),
            ], 'Appointments retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve all appointments', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve appointments', 500, [
                'error' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Get my appointments (only created by logged-in user)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getMyAppointments(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Get base query
            $query = $this->getAppointmentBaseQuery();

            // Filter by created_by (only user's own appointments)
            $query->where('created_by', $user->id);

            // Apply additional filters from request
            $query = $this->applyRequestFilters($query, $request);
            $query = $this->applyLastThreeMonthsFilter($query, 'appointments.created_at');

            $query->orderByDesc('appointments.date')
                  ->orderByDesc('appointments.created_at')
                  ->orderByDesc('appointments.id');

            $appointments = $query->get();

            return $this->successResponse([
                'appointments' => $appointments,
                'total' => $appointments->count(),
                ...$this->lastThreeMonthsDateRange(),
                'created_by' => $user->id,
                'user_name' => $user->first_name . ' ' . $user->last_name,
            ], 'My appointments retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve my appointments', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve my appointments', 500, [
                'error' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Get today's appointments with role-based hierarchy access
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getTodayAppointments(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            // Load user relationships
            $user->load(['roles', 'teams', 'departments']);

            // Get base query
            $query = $this->getAppointmentBaseQuery();

            // Apply role-based filters
            $query = $this->applyRoleBasedFilters($query, $user);

            // Filter for today's date
            $query->whereDate('date', today());

            // Apply additional filters from request (excluding date filters)
            $query = $this->applyRequestFilters($query, $request, ['date_from', 'date_to']);

            // Order by time slot (ascending for today's schedule)
            $query->orderBy('appointments.date', 'asc')
                  ->orderBy('appointments.time_slot_id', 'asc')
                  ->orderBy('appointments.id', 'asc');

            // Paginate results
            $perPage = $request->get('per_page', 15);
            $appointments = $query->cursorPaginate($perPage);

            return $this->successResponse([
                'appointments' => $appointments,
                'today' => today()->format('Y-m-d'),
                'user_role' => $this->getUserRoleInfo($user),
                'access_level' => $this->determineAccessLevel($user)
            ], 'Today\'s appointments retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve today appointments', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('Failed to retrieve today appointments', 500, [
                'error' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    /**
     * Get base query for appointments with common eager loads
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function getAppointmentBaseQuery()
    {
        return Appointment::with([
            'followupBusiness:id,name,category,type,phone,email,website',
            'followupBusiness.authPersons:id,title,firstname,lastname,designation,primaryemail,primarymobile,is_primary',
            'timeSlot:id,name,start_time,end_time,duration_minutes',
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
                // Admin can see all appointments - no additional filter needed
                Log::info('Admin accessing all appointments', ['user_id' => $user->id]);
                break;

            case 'manager':
                // Manager (Lead Generation dept + has team): See team members' appointments + own
                $teamMemberIds = $this->getTeamMemberIds($user);
                $teamMemberIds[] = $user->id; // Include self
                
                Log::info('Manager accessing team appointments', [
                    'user_id' => $user->id,
                    'team_member_count' => count($teamMemberIds)
                ]);

                $query->whereIn('created_by', $teamMemberIds);
                break;

            case 'executive':
                // Executive (Lead Generation dept): See only own appointments
                Log::info('Executive accessing own appointments', ['user_id' => $user->id]);
                $query->where('created_by', $user->id);
                break;

            default:
                // Default: Only own appointments for safety
                Log::warning('Unknown user type, defaulting to own appointments only', [
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
        // Status filter
        if ($request->has('status') && !in_array('status', $excludeFilters)) {
            $query->where('status', $request->status);
        }

        // Current status filter
        if ($request->has('current_status') && !in_array('current_status', $excludeFilters)) {
            $query->where('current_status', $request->current_status);
        }

        // Date range filters
        if ($request->has('date_from') && !in_array('date_from', $excludeFilters)) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !in_array('date_to', $excludeFilters)) {
            $query->where('date', '<=', $request->date_to);
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
