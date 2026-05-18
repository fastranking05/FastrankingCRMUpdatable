<?php

namespace App\Http\Controllers\Api\Seo;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\SeoDetail;
use App\Models\SeoQuestionAnswer;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SeoViewController extends BaseApiController
{
    /**
     * Get comprehensive SEO view with all details
     * Combines business details, auth persons, comments, and SEO details
     *
     * Manager (Digital Marketing): Can see own + team members' data
     * Executive (Digital Marketing): Can see only own data
     * Admin: Can see all data
     */
    public function comprehensiveView(): JsonResponse
    {
        $user = auth()->user();
        $query = SeoDetail::with([
            'assignedUser:id,first_name,last_name,email',
            'questionAnswers.question:id,name,answer_type,dropdown_options',
            'followupBusiness' => function ($query) {
                $query->with([
                    'authPersons' => function ($query) {
                        $query->select([
                            'followup_auth_persons.id',
                            'followup_auth_persons.title',
                            'followup_auth_persons.firstname',
                            'followup_auth_persons.middlename',
                            'followup_auth_persons.lastname',
                            'followup_auth_persons.designation',
                            'followup_auth_persons.primaryemail',
                            'followup_auth_persons.primarymobile',
                            'followup_auth_persons.is_primary'
                        ]);
                    },
                    'comments' => function ($query) {
                        $query->with('creator:id,first_name,last_name,email')
                              ->orderBy('created_at', 'desc');
                    },
                    'creator:id,first_name,last_name,email',
                    'appointments' => function ($query) {
                        $query->with('timeSlot:id,start_time,end_time')
                              ->orderBy('date', 'desc')
                              ->limit(5); // Latest 5 appointments
                    }
                ]);
            }
        ]);

        // Apply role-based filtering
        $this->applyRoleBasedFiltering($query, $user);

        $seoRecords = $query->orderBy('updated_at', 'desc')->get();

        // Format the comprehensive response
        $formattedData = $seoRecords->map(function ($seoDetail) {
            $business = null;
            if ($seoDetail->followupBusiness) {
                $followupBusiness = $seoDetail->followupBusiness;

                // Format auth persons
                $authPersons = $followupBusiness->authPersons->map(function ($person) {
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

                // Format comments
                $comments = $followupBusiness->comments->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'comment' => $comment->comment,
                        'old_status' => $comment->old_status,
                        'new_status' => $comment->new_status,
                        'created_at' => $comment->created_at,
                        'updated_at' => $comment->updated_at,
                        'creator' => $comment->creator ? [
                            'id' => $comment->creator->id,
                            'first_name' => $comment->creator->first_name,
                            'last_name' => $comment->creator->last_name,
                            'email' => $comment->creator->email
                        ] : null
                    ];
                });

                // Format appointments
                $appointments = $followupBusiness->appointments->map(function ($appointment) {
                    return [
                        'id' => $appointment->id,
                        'date' => $appointment->date,
                        'current_status' => $appointment->current_status,
                        'source' => $appointment->source,
                        'time_slot' => $appointment->timeSlot ? [
                            'id' => $appointment->timeSlot->id,
                            'start_time' => $appointment->timeSlot->start_time ? date('H:i:s', strtotime($appointment->timeSlot->start_time)) : null,
                            'end_time' => $appointment->timeSlot->end_time ? date('H:i:s', strtotime($appointment->timeSlot->end_time)) : null
                        ] : null,
                        'created_at' => $appointment->created_at
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
                    'created_at' => $followupBusiness->created_at,
                    'updated_at' => $followupBusiness->updated_at,
                    'creator' => $followupBusiness->creator ? [
                        'id' => $followupBusiness->creator->id,
                        'first_name' => $followupBusiness->creator->first_name,
                        'last_name' => $followupBusiness->creator->last_name,
                        'email' => $followupBusiness->creator->email
                    ] : null,
                    'auth_persons' => $authPersons,
                    'comments' => $comments,
                    'appointments' => $appointments
                ];
            }

            // Format question answers
            $questionAnswers = $seoDetail->questionAnswers->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    'question' => $answer->question ? [
                        'id' => $answer->question->id,
                        'name' => $answer->question->name,
                        'answer_type' => $answer->question->answer_type,
                        'dropdown_options' => $answer->question->dropdown_options,
                    ] : null,
                    'answer' => $answer->answer,
                    'comments' => $answer->comments,
                    'created_at' => $answer->created_at,
                    'updated_at' => $answer->updated_at
                ];
            });

            return [
                'seo_details' => [
                    'id' => $seoDetail->id,
                    'followup_business_id' => $seoDetail->followup_business_id,
                    'status' => $seoDetail->status,
                    'reason' => $seoDetail->reason,
                    'audited_website' => $seoDetail->audited_website,
                    'audited_date' => $seoDetail->audited_date,
                    'auditor' => $seoDetail->auditor,
                    'assigned_user' => $seoDetail->assignedUser ? [
                        'id' => $seoDetail->assignedUser->id,
                        'first_name' => $seoDetail->assignedUser->first_name,
                        'last_name' => $seoDetail->assignedUser->last_name,
                        'email' => $seoDetail->assignedUser->email
                    ] : null,
                    'created_at' => $seoDetail->created_at,
                    'updated_at' => $seoDetail->updated_at,
                    'question_answers' => $questionAnswers
                ],
                'business_details' => $business
            ];
        });

        return $this->successResponse($formattedData, 'Comprehensive SEO view retrieved successfully');
    }

    /**
     * Get comprehensive SEO view for a specific business
     *
     * @param int $businessId
     * @return JsonResponse
     */
    public function comprehensiveViewByBusiness(int $businessId): JsonResponse
    {
        $user = auth()->user();

        $seoDetail = SeoDetail::with([
            'assignedUser:id,first_name,last_name,email',
            'questionAnswers.question:id,name,answer_type,dropdown_options',
            'followupBusiness' => function ($query) {
                $query->with([
                    'authPersons' => function ($query) {
                        $query->select([
                            'followup_auth_persons.id',
                            'followup_auth_persons.title',
                            'followup_auth_persons.firstname',
                            'followup_auth_persons.middlename',
                            'followup_auth_persons.lastname',
                            'followup_auth_persons.designation',
                            'followup_auth_persons.primaryemail',
                            'followup_auth_persons.primarymobile',
                            'followup_auth_persons.is_primary'
                        ]);
                    },
                    'comments' => function ($query) {
                        $query->with('creator:id,first_name,last_name,email')
                              ->orderBy('created_at', 'desc');
                    },
                    'creator:id,first_name,last_name,email',
                    'appointments' => function ($query) {
                        $query->with('timeSlot:id,start_time,end_time')
                              ->orderBy('date', 'desc');
                    }
                ]);
            }
        ])->where('followup_business_id', $businessId)->first();

        if (!$seoDetail) {
            return $this->errorResponse('SEO details not found for this business', 404);
        }

        // Apply role-based filtering
        if (!$this->canUserAccessSeoDetail($user, $seoDetail)) {
            return $this->errorResponse('You do not have permission to access this SEO record', 403);
        }

        // Format the comprehensive response (same as above but for single record)
        $followupBusiness = $seoDetail->followupBusiness;

        // Format auth persons
        $authPersons = $followupBusiness->authPersons->map(function ($person) {
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

        // Format comments
        $comments = $followupBusiness->comments->map(function ($comment) {
            return [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'old_status' => $comment->old_status,
                'new_status' => $comment->new_status,
                'created_at' => $comment->created_at,
                'updated_at' => $comment->updated_at,
                'creator' => $comment->creator ? [
                    'id' => $comment->creator->id,
                    'first_name' => $comment->creator->first_name,
                    'last_name' => $comment->creator->last_name,
                    'email' => $comment->creator->email
                ] : null
            ];
        });

        // Format appointments
        $appointments = $followupBusiness->appointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'date' => $appointment->date,
                'current_status' => $appointment->current_status,
                'source' => $appointment->source,
                'time_slot' => $appointment->timeSlot ? [
                    'id' => $appointment->timeSlot->id,
                    'start_time' => $appointment->timeSlot->start_time ? date('H:i:s', strtotime($appointment->timeSlot->start_time)) : null,
                    'end_time' => $appointment->timeSlot->end_time ? date('H:i:s', strtotime($appointment->timeSlot->end_time)) : null
                ] : null,
                'created_at' => $appointment->created_at
            ];
        });

        // Format question answers
        $questionAnswers = $seoDetail->questionAnswers->map(function ($answer) {
            return [
                'id' => $answer->id,
                'question' => $answer->question ? [
                    'id' => $answer->question->id,
                    'name' => $answer->question->name,
                    'answer_type' => $answer->question->answer_type,
                    'dropdown_options' => $answer->question->dropdown_options,
                ] : null,
                'answer' => $answer->answer,
                'comments' => $answer->comments,
                'created_at' => $answer->created_at,
                'updated_at' => $answer->updated_at
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
            'created_at' => $followupBusiness->created_at,
            'updated_at' => $followupBusiness->updated_at,
            'creator' => $followupBusiness->creator ? [
                'id' => $followupBusiness->creator->id,
                'first_name' => $followupBusiness->creator->first_name,
                'last_name' => $followupBusiness->creator->last_name,
                'email' => $followupBusiness->creator->email
            ] : null,
            'auth_persons' => $authPersons,
            'comments' => $comments,
            'appointments' => $appointments
        ];

        $comprehensiveData = [
            'seo_details' => [
                'id' => $seoDetail->id,
                'followup_business_id' => $seoDetail->followup_business_id,
                'status' => $seoDetail->status,
                'reason' => $seoDetail->reason,
                'audited_website' => $seoDetail->audited_website,
                'audited_date' => $seoDetail->audited_date,
                'auditor' => $seoDetail->auditor,
                'assigned_user' => $seoDetail->assignedUser ? [
                    'id' => $seoDetail->assignedUser->id,
                    'first_name' => $seoDetail->assignedUser->first_name,
                    'last_name' => $seoDetail->assignedUser->last_name,
                    'email' => $seoDetail->assignedUser->email
                ] : null,
                'created_at' => $seoDetail->created_at,
                'updated_at' => $seoDetail->updated_at,
                'question_answers' => $questionAnswers
            ],
            'business_details' => $business
        ];

        return $this->successResponse($comprehensiveData, 'Comprehensive SEO view for business retrieved successfully');
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
     * Check if user can access specific SEO detail
     */
    private function canUserAccessSeoDetail($user, $seoDetail): bool
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
            return true;
        }

        if ($role && $department && $role->name === 'Manager' && $department->name === 'Digital Marketing') {
            $teamMemberIds = $this->getTeamMemberIds($user);
            return in_array($seoDetail->assigned_user, $teamMemberIds);
        }

        if ($role && $department && $role->name === 'Executive' && $department->name === 'Digital Marketing') {
            return $seoDetail->assigned_user === $user->id;
        }

        return false;
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
