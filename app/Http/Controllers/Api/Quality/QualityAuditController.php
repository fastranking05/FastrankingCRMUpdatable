<?php

namespace App\Http\Controllers\Api\Quality;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Concerns\AppliesLastThreeMonthsFilter;
use App\Models\Quality;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class QualityAuditController extends BaseApiController
{
    use AppliesLastThreeMonthsFilter;

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
            'appointment.followupBusiness:id,name,category,type,website,phone,email',
            'appointment.timeSlot:id,start_time,end_time'
        ]);

        // Filter by unqualified status
        $query->where('auditstatus', 'pending');

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);
        $this->applyLastThreeMonthsFilter($query, 'qualities.created_at');

        // Get latest quality per appointment
        $this->getLatestQualities($query);

        $formattedAudits = $this->formatQualityAudits($query->orderBy('created_at', 'desc')->get());

        return $this->successResponse([
            'audits' => $formattedAudits,
            'total' => $formattedAudits->count(),
            ...$this->lastThreeMonthsDateRange(),
        ], 'Audit pending quality data retrieved successfully');
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
            'appointment.followupBusiness:id,name,category,type,website,phone,email',
            'appointment.timeSlot:id,start_time,end_time'
        ]);

        // Filter by qualified status
        $query->where('auditstatus', 'qualified');

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);
        $this->applyLastThreeMonthsFilter($query, 'qualities.created_at');

        // Get latest quality per appointment
        $this->getLatestQualities($query);

        $formattedAudits = $this->formatQualityAudits($query->orderBy('created_at', 'desc')->get());

        return $this->successResponse([
            'audits' => $formattedAudits,
            'total' => $formattedAudits->count(),
            ...$this->lastThreeMonthsDateRange(),
        ], 'Audit completed quality data retrieved successfully');
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
            'appointment.followupBusiness:id,name,category,type,website,phone,email',
            'appointment.timeSlot:id,start_time,end_time'
        ]);

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);
        $this->applyLastThreeMonthsFilter($query, 'qualities.created_at');

        // Get latest quality per appointment
        $this->getLatestQualities($query);

        $formattedAudits = $this->formatQualityAudits($query->orderBy('created_at', 'desc')->get());

        return $this->successResponse([
            'audits' => $formattedAudits,
            'total' => $formattedAudits->count(),
            ...$this->lastThreeMonthsDateRange(),
        ], 'All quality data retrieved successfully');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Quality>  $audits
     */
    private function formatQualityAudits($audits)
    {
        return $audits->map(function ($audit) {
            $business = null;
            if ($audit->appointment && $audit->appointment->followupBusiness) {
                $followupBusiness = $audit->appointment->followupBusiness;

                $authPersons = DB::table('followup_business_auth_person')
                    ->join('followup_auth_persons', 'followup_auth_persons.id', '=', 'followup_business_auth_person.followup_auth_person_id')
                    ->where('followup_business_auth_person.followup_business_id', $followupBusiness->id)
                    ->select([
                        'followup_auth_persons.id',
                        'followup_auth_persons.title',
                        'followup_auth_persons.firstname',
                        'followup_auth_persons.middlename',
                        'followup_auth_persons.lastname',
                        'followup_auth_persons.designation',
                        'followup_auth_persons.primaryemail',
                        'followup_auth_persons.primarymobile',
                        'followup_auth_persons.is_primary',
                    ])
                    ->get()
                    ->map(function ($person) {
                        return [
                            'id' => $person->id,
                            'title' => $person->title,
                            'firstname' => $person->firstname,
                            'middlename' => $person->middlename,
                            'lastname' => $person->lastname,
                            'designation' => $person->designation,
                            'primaryemail' => $person->primaryemail,
                            'primarymobile' => $person->primarymobile,
                            'is_primary' => $person->is_primary,
                        ];
                    });

                $business = [
                    'id' => $followupBusiness->id,
                    'name' => $followupBusiness->name,
                    'category' => $followupBusiness->category,
                    'type' => $followupBusiness->type,
                    'website' => $followupBusiness->website,
                    'phone' => $followupBusiness->phone,
                    'email' => $followupBusiness->email,
                    'auth_persons' => $authPersons,
                ];
            }

            return [
                'id' => $audit->id,
                'appointment_id' => $audit->appointment_id,
                'auditstatus' => $audit->auditstatus,
                'status' => $audit->status,
                'score' => $audit->score,
                'assigned_user' => $audit->assignedUser,
                'meeting_link' => $audit->meeting_link,
                'created_at' => $audit->created_at,
                'updated_at' => $audit->updated_at,
                'answers' => $audit->answers,
                'business' => $business,
                'appointment_date' => $audit->appointment ? $audit->appointment->date : null,
                'appointment_source' => $audit->appointment ? $audit->appointment->source : null,
                'appointment_current_status' => $audit->appointment ? $audit->appointment->current_status : null,
                'appointment_slot' => $audit->appointment && $audit->appointment->timeSlot ? [
                    'id' => $audit->appointment->timeSlot->id,
                    'start_time' => $audit->appointment->timeSlot->start_time ? date('H:i:s', strtotime($audit->appointment->timeSlot->start_time)) : null,
                    'end_time' => $audit->appointment->timeSlot->end_time ? date('H:i:s', strtotime($audit->appointment->timeSlot->end_time)) : null,
                ] : null,
            ];
        });
    }

    /**
     * Get latest quality per appointment
     */
    private function getLatestQualities($query)
    {
        return $query->select('qualities.*')
            ->join(DB::raw('(SELECT appointment_id, MAX(created_at) as max_created_at 
                           FROM qualities 
                           GROUP BY appointment_id) latest'), function($join) {
                $join->on('qualities.appointment_id', '=', 'latest.appointment_id')
                     ->on('qualities.created_at', '=', 'latest.max_created_at');
            });
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
