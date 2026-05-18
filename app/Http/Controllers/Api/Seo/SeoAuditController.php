<?php

namespace App\Http\Controllers\Api\Seo;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\SeoDetail;
use App\Models\SeoQuestionAnswer;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SeoAuditController extends BaseApiController
{
    /**
     * Get SEO audit pending data (Pending status)
     * Manager (Digital Marketing): Can see own + team members' data
     * Executive (Digital Marketing): Can see only own data
     * Admin: Can see all data
     */
    public function auditPending(): JsonResponse
    {
        $user = auth()->user();
        $query = SeoDetail::with([
            'assignedUser:id,first_name,last_name,email',
            'questionAnswers.question:id,name,answer_type,dropdown_options',
            'followupBusiness:id,name,category,type,website,phone,email',
            'followupBusiness.authPersons'
        ]);

        // Filter by pending status
        $query->where('status', 'Pending');

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);

        $audits = $query->orderBy('created_at', 'desc')->get();

        // Format the response
        $formattedAudits = $audits->map(function ($audit) {
            $business = null;
            if ($audit->followupBusiness) {
                $followupBusiness = $audit->followupBusiness;

                // Get auth persons specifically for this business
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
                        'followup_auth_persons.is_primary'
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
                            'is_primary' => $person->is_primary
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
                    'auth_persons' => $authPersons
                ];
            }

            return [
                'id' => $audit->id,
                'followup_business_id' => $audit->followup_business_id,
                'status' => $audit->status,
                'reason' => $audit->reason,
                'audited_website' => $audit->audited_website,
                'audited_date' => $audit->audited_date,
                'auditor' => $audit->auditor,
                'assigned_user' => $audit->assignedUser,
                'created_at' => $audit->created_at,
                'updated_at' => $audit->updated_at,
                'question_answers' => $audit->questionAnswers,
                'business' => $business
            ];
        });

        return $this->successResponse($formattedAudits, 'SEO audit pending data retrieved successfully');
    }

    /**
     * Get SEO audit completed data (Audit Completed status)
     * Manager (Digital Marketing): Can see own + team members' data
     * Executive (Digital Marketing): Can see only own data
     * Admin: Can see all data
     */
    public function auditCompleted(): JsonResponse
    {
        $user = auth()->user();
        $query = SeoDetail::with([
            'assignedUser:id,first_name,last_name,email',
            'questionAnswers.question:id,name,answer_type,dropdown_options',
            'followupBusiness:id,name,category,type,website,phone,email',
            'followupBusiness.authPersons'
        ]);

        // Filter by completed status
        $query->where('status', 'Audit Completed');

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);

        $audits = $query->orderBy('updated_at', 'desc')->get();

        // Format the response
        $formattedAudits = $audits->map(function ($audit) {
            $business = null;
            if ($audit->followupBusiness) {
                $followupBusiness = $audit->followupBusiness;

                // Get auth persons specifically for this business
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
                        'followup_auth_persons.is_primary'
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
                            'is_primary' => $person->is_primary
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
                    'auth_persons' => $authPersons
                ];
            }

            return [
                'id' => $audit->id,
                'followup_business_id' => $audit->followup_business_id,
                'status' => $audit->status,
                'reason' => $audit->reason,
                'audited_website' => $audit->audited_website,
                'audited_date' => $audit->audited_date,
                'auditor' => $audit->auditor,
                'assigned_user' => $audit->assignedUser,
                'created_at' => $audit->created_at,
                'updated_at' => $audit->updated_at,
                'question_answers' => $audit->questionAnswers,
                'business' => $business
            ];
        });

        return $this->successResponse($formattedAudits, 'SEO audit completed data retrieved successfully');
    }

    /**
     * Get SEO not applicable data (Not Applicable status)
     * Manager (Digital Marketing): Can see own + team members' data
     * Executive (Digital Marketing): Can see only own data
     * Admin: Can see all data
     */
    public function notApplicable(): JsonResponse
    {
        $user = auth()->user();
        $query = SeoDetail::with([
            'assignedUser:id,first_name,last_name,email',
            'questionAnswers.question:id,name,answer_type,dropdown_options',
            'followupBusiness:id,name,category,type,website,phone,email',
            'followupBusiness.authPersons'
        ]);

        // Filter by not applicable status
        $query->where('status', 'Not Applicable');

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);

        $audits = $query->orderBy('updated_at', 'desc')->get();

        // Format the response
        $formattedAudits = $audits->map(function ($audit) {
            $business = null;
            if ($audit->followupBusiness) {
                $followupBusiness = $audit->followupBusiness;

                // Get auth persons specifically for this business
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
                        'followup_auth_persons.is_primary'
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
                            'is_primary' => $person->is_primary
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
                    'auth_persons' => $authPersons
                ];
            }

            return [
                'id' => $audit->id,
                'followup_business_id' => $audit->followup_business_id,
                'status' => $audit->status,
                'reason' => $audit->reason,
                'audited_website' => $audit->audited_website,
                'audited_date' => $audit->audited_date,
                'auditor' => $audit->auditor,
                'assigned_user' => $audit->assignedUser,
                'created_at' => $audit->created_at,
                'updated_at' => $audit->updated_at,
                'question_answers' => $audit->questionAnswers,
                'business' => $business
            ];
        });

        return $this->successResponse($formattedAudits, 'SEO not applicable data retrieved successfully');
    }

    /**
     * Get all SEO data (any status)
     * Manager (Digital Marketing): Can see own + team members' data
     * Executive (Digital Marketing): Can see only own data
     * Admin: Can see all data
     */
    public function allAudits(): JsonResponse
    {
        $user = auth()->user();
        $query = SeoDetail::with([
            'assignedUser:id,first_name,last_name,email',
            'questionAnswers.question:id,name,answer_type,dropdown_options',
            'followupBusiness:id,name,category,type,website,phone,email',
            'followupBusiness.authPersons'
        ]);

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);

        $audits = $query->orderBy('updated_at', 'desc')->get();

        // Format the response
        $formattedAudits = $audits->map(function ($audit) {
            $business = null;
            if ($audit->followupBusiness) {
                $followupBusiness = $audit->followupBusiness;

                // Get auth persons specifically for this business
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
                        'followup_auth_persons.is_primary'
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
                            'is_primary' => $person->is_primary
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
                    'auth_persons' => $authPersons
                ];
            }

            return [
                'id' => $audit->id,
                'followup_business_id' => $audit->followup_business_id,
                'status' => $audit->status,
                'reason' => $audit->reason,
                'audited_website' => $audit->audited_website,
                'audited_date' => $audit->audited_date,
                'auditor' => $audit->auditor,
                'assigned_user' => $audit->assignedUser,
                'created_at' => $audit->created_at,
                'updated_at' => $audit->updated_at,
                'question_answers' => $audit->questionAnswers,
                'business' => $business
            ];
        });

        return $this->successResponse($formattedAudits, 'All SEO data retrieved successfully');
    }

    /**
     * Apply role-based filtering to SEO data queries
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
