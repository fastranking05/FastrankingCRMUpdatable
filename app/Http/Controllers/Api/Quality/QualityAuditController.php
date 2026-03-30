<?php

namespace App\Http\Controllers\Api\Quality;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Quality;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class QualityAuditController extends BaseApiController
{
    /**
     * Get audit pending quality data (unqualified status)
     * Manager (Quality Control): Can see own + team members' data
     * Executive (Quality Control): Can see only own data
     * Admin: Can see all data
     */
    public function auditPending(): JsonResponse
    {
        $user = auth()->user();
        $query = Quality::with([
            'assignedUser:id,first_name,last_name,email',
            'answers.question:id,question',
            'appointment'
        ]);

        // Filter by unqualified status
        $query->where('auditstatus', 'unqualified');

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);

        $audits = $query->orderBy('created_at', 'desc')->get();

        return $this->successResponse($audits, 'Audit pending quality data retrieved successfully');
    }

    /**
     * Get audit completed quality data (qualified status)
     * Executive (Quality Control): Can see only own data
     * Admin: Can see all data
     */
    public function auditCompleted(): JsonResponse
    {
        $user = auth()->user();
        $query = Quality::with([
            'assignedUser:id,first_name,last_name,email',
            'answers.question:id,question',
            'appointment'
        ]);

        // Filter by qualified status
        $query->where('auditstatus', 'qualified');

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);

        $audits = $query->orderBy('created_at', 'desc')->get();

        return $this->successResponse($audits, 'Audit completed quality data retrieved successfully');
    }

    /**
     * Get all quality data based on role
     * Manager (Quality Control): Can see own + team members' data
     * Executive (Quality Control): Can see only own data
     * Admin: Can see all data
     */
    public function allAudits(): JsonResponse
    {
        $user = auth()->user();
        $query = Quality::with([
            'assignedUser:id,first_name,last_name,email',
            'answers.question:id,question',
            'appointment'
        ]);

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);

        $audits = $query->orderBy('created_at', 'desc')->get();

        return $this->successResponse($audits, 'All quality data retrieved successfully');
    }

    /**
     * Apply role-based filtering to quality data queries
     */
    private function applyRoleBasedFiltering($query, $user): void
    {
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

        if ($role && $department && $role->name === 'Manager' && $department->name === 'Quality Control') {
            // Manager can see own + team members' data
            $teamMemberIds = $this->getTeamMemberIds($user);
            $query->whereIn('assigned_user', $teamMemberIds);
        } elseif ($role && $department && $role->name === 'Executive' && $department->name === 'Quality Control') {
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
