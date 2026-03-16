<?php

namespace App\Http\Controllers\Api\Followup;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\FollowupBusiness;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupDetail;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FollowupController extends BaseApiController
{
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

            // Pagination
            $perPage = $request->get('per_page', 15);
            $followups = $query->paginate($perPage);

            return $this->successResponse($followups, 'Follow-up records retrieved successfully');
        }, 'Follow-up list retrieval');
    }

    /**
     * Store a complete follow-up record with all related data.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Business Details
            'business.name' => 'required|string|max:255',
            'business.category' => 'nullable|string|max:255',
            'business.type' => 'nullable|string|max:255',
            'business.website' => 'nullable|url|max:255',
            'business.phone' => 'nullable|string|unique:followup_businesses,phone',
            'business.email' => 'nullable|email|max:255',
            
            // Auth Persons (array)
            'auth_persons' => 'nullable|array',
            'auth_persons.*.title' => 'nullable|string|max:50',
            'auth_persons.*.firstname' => 'required|string|max:255',
            'auth_persons.*.middlename' => 'nullable|string|max:255',
            'auth_persons.*.lastname' => 'required|string|max:255',
            'auth_persons.*.is_primary' => 'nullable|boolean',
            'auth_persons.*.designation' => 'nullable|string|max:255',
            'auth_persons.*.gender' => 'nullable|in:male,female,other',
            'auth_persons.*.dob' => 'nullable|date',
            'auth_persons.*.primaryphone' => 'nullable|string|unique:followup_auth_persons,primaryphone',
            'auth_persons.*.altphone' => 'nullable|string|unique:followup_auth_persons,altphone',
            'auth_persons.*.primarymobile' => 'nullable|string|unique:followup_auth_persons,primarymobile',
            'auth_persons.*.altmobile' => 'nullable|string|unique:followup_auth_persons,altmobile',
            'auth_persons.*.primaryemail' => 'required|email|unique:followup_auth_persons,primaryemail',
            'auth_persons.*.altemail' => 'nullable|email|unique:followup_auth_persons,altemail',
            
            // Follow-up Details (array)
            'followup_details' => 'nullable|array',
            'followup_details.*.source' => 'nullable|string|max:255',
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
            // Create Business
            $businessData = $request->business;
            $businessData['created_by'] = auth()->id();
            $business = FollowupBusiness::create($businessData);

            // Create Auth Persons if provided
            $authPersons = [];
            if ($request->has('auth_persons')) {
                foreach ($request->auth_persons as $personData) {
                    $personData['created_by'] = auth()->id();
                    $person = FollowupAuthPerson::create($personData);
                    $authPersons[] = $person;
                    
                    // Attach to business
                    $business->authPersons()->attach($person->id);
                }
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

            return $this->successResponse($business, 'Complete follow-up record created successfully', 201);
        }, 'Follow-up creation', $request->only(['business.name', 'business.email']));
    }

    /**
     * Display the specified complete follow-up record.
     */
    public function show(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $followup = FollowupBusiness::with([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails',
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ])->find($id);

            if (!$followup) {
                return $this->errorResponse('Follow-up record not found', 404);
            }

            return $this->successResponse($followup, 'Follow-up record retrieved successfully');
        }, 'Follow-up retrieval', ['followup_id' => $id]);
    }

    /**
     * Update the complete follow-up record.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $followup = FollowupBusiness::find($id);

        if (!$followup) {
            return $this->errorResponse('Follow-up record not found', 404);
        }

        $validator = Validator::make($request->all(), [
            // Business Details
            'business.name' => 'sometimes|required|string|max:255',
            'business.category' => 'nullable|string|max:255',
            'business.type' => 'nullable|string|max:255',
            'business.website' => 'nullable|url|max:255',
            'business.phone' => 'nullable|string|unique:followup_businesses,phone,' . $id,
            'business.email' => 'nullable|email|max:255',
            
            // Auth Persons (array for sync)
            'auth_persons' => 'nullable|array',
            'auth_persons.*.id' => 'sometimes|required|exists:followup_auth_persons,id',
            'auth_persons.*.title' => 'nullable|string|max:50',
            'auth_persons.*.firstname' => 'sometimes|required|string|max:255',
            'auth_persons.*.middlename' => 'nullable|string|max:255',
            'auth_persons.*.lastname' => 'sometimes|required|string|max:255',
            'auth_persons.*.is_primary' => 'nullable|boolean',
            'auth_persons.*.designation' => 'nullable|string|max:255',
            'auth_persons.*.gender' => 'nullable|in:male,female,other',
            'auth_persons.*.dob' => 'nullable|date',
            'auth_persons.*.primaryphone' => 'nullable|string|unique:followup_auth_persons,primaryphone',
            'auth_persons.*.altphone' => 'nullable|string|unique:followup_auth_persons,altphone',
            'auth_persons.*.primarymobile' => 'nullable|string|unique:followup_auth_persons,primarymobile',
            'auth_persons.*.altmobile' => 'nullable|string|unique:followup_auth_persons,altmobile',
            'auth_persons.*.primaryemail' => 'sometimes|required|email|unique:followup_auth_persons,primaryemail',
            'auth_persons.*.altemail' => 'nullable|email|unique:followup_auth_persons,altemail',
            
            // Follow-up Details (array)
            'followup_details' => 'nullable|array',
            'followup_details.*.id' => 'sometimes|required|exists:followup_details,id',
            'followup_details.*.source' => 'nullable|string|max:255',
            'followup_details.*.status' => 'nullable|string|max:255',
            'followup_details.*.date' => 'nullable|date',
            'followup_details.*.time' => 'nullable|date_format:H:i',
            
            // Comments (array) - directly linked to business
            'comments' => 'nullable|array',
            'comments.*.id' => 'sometimes|required|exists:comments,id',
            'comments.*.comment' => 'sometimes|required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $followup) {
            // Update Business
            if ($request->has('business')) {
                $followup->update($request->business);
            }

            // Update Auth Persons if provided
            if ($request->has('auth_persons')) {
                $authPersonIds = [];
                foreach ($request->auth_persons as $personData) {
                    if (isset($personData['id'])) {
                        // Update existing
                        $person = FollowupAuthPerson::find($personData['id']);
                        if ($person) {
                            $person->update($personData);
                            $authPersonIds[] = $person->id;
                        }
                    } else {
                        // Create new
                        $personData['created_by'] = auth()->id();
                        $person = FollowupAuthPerson::create($personData);
                        $authPersonIds[] = $person->id;
                    }
                }
                // Sync with business
                $followup->authPersons()->sync($authPersonIds);
            }

            // Update Follow-up Details if provided
            if ($request->has('followup_details')) {
                foreach ($request->followup_details as $detailData) {
                    if (isset($detailData['id'])) {
                        // Update existing
                        $detail = FollowupDetail::find($detailData['id']);
                        if ($detail && $detail->followup_business_id === $followup->id) {
                            $detail->update($detailData);
                        }
                    } else {
                        // Create new
                        $detailData['followup_business_id'] = $followup->id;
                        $detailData['created_by'] = auth()->id();
                        FollowupDetail::create($detailData);
                    }
                }
            }

            // Update Comments if provided
            if ($request->has('comments')) {
                foreach ($request->comments as $commentData) {
                    if (isset($commentData['id'])) {
                        // Update existing
                        $comment = Comment::find($commentData['id']);
                        if ($comment && $comment->followup_business_id === $followup->id) {
                            $comment->update($commentData);
                        }
                    } else {
                        // Create new
                        $followup->comments()->create([
                            'comment' => $commentData['comment'],
                            'old_status' => $commentData['old_status'] ?? null,
                            'new_status' => $commentData['new_status'] ?? null,
                            'created_by' => auth()->id(),
                        ]);
                    }
                }
            }

            // Load complete relationship data
            $followup->load([
                'creator:id,first_name,last_name',
                'authPersons',
                'followupDetails',
                'comments' => function ($query) {
                    $query->with('creator:id,first_name,last_name')->orderBy('created_at', 'desc');
                }
            ]);

            return $this->successResponse($followup, 'Complete follow-up record updated successfully');
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
}
