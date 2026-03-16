<?php

namespace App\Http\Controllers\Api\Followup;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\FollowupBusiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FollowupBusinessController extends BaseApiController
{
    /**
     * Display a listing of follow-up businesses.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = FollowupBusiness::with(['creator:id,first_name,last_name', 'authPersons']);

            // Filter by category
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Filter by name
            if ($request->has('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $businesses = $query->paginate($perPage);

            return $this->successResponse($businesses, 'Follow-up businesses retrieved successfully');
        }, 'Follow-up business list retrieval');
    }

    /**
     * Store a newly created follow-up business.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|unique:followup_businesses,phone',
            'email' => 'nullable|email|max:255',
            'auth_person_ids' => 'nullable|array',
            'auth_person_ids.*' => 'exists:followup_auth_persons,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $data = $request->all();
            $data['created_by'] = auth()->id();

            $business = FollowupBusiness::create($data);

            // Attach authorized persons if provided
            if ($request->has('auth_person_ids')) {
                $business->authPersons()->attach($request->auth_person_ids);
            }

            $business->load(['creator:id,first_name,last_name', 'authPersons']);

            return $this->successResponse($business, 'Follow-up business created successfully', 201);
        }, 'Follow-up business creation', $request->only(['name', 'email']));
    }

    /**
     * Display the specified follow-up business.
     */
    public function show(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $business = FollowupBusiness::with(['creator:id,first_name,last_name', 'authPersons', 'followupDetails'])->find($id);

            if (!$business) {
                return $this->errorResponse('Follow-up business not found', 404);
            }

            return $this->successResponse($business, 'Follow-up business retrieved successfully');
        }, 'Follow-up business retrieval', ['business_id' => $id]);
    }

    /**
     * Update the specified follow-up business.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $business = FollowupBusiness::find($id);

        if (!$business) {
            return $this->errorResponse('Follow-up business not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'category' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'website' => 'nullable|url|max:255',
            'phone' => 'nullable|string|unique:followup_businesses,phone,' . $id,
            'email' => 'nullable|email|max:255',
            'auth_person_ids' => 'nullable|array',
            'auth_person_ids.*' => 'exists:followup_auth_persons,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $business) {
            $business->update($request->all());

            // Sync authorized persons if provided
            if ($request->has('auth_person_ids')) {
                $business->authPersons()->sync($request->auth_person_ids);
            }

            $business->load(['creator:id,first_name,last_name', 'authPersons']);

            return $this->successResponse($business, 'Follow-up business updated successfully');
        }, 'Follow-up business update', ['business_id' => $business->id]);
    }

    /**
     * Remove the specified follow-up business.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $business = FollowupBusiness::find($id);

            if (!$business) {
                return $this->errorResponse('Follow-up business not found', 404);
            }

            // Detach all relationships
            $business->authPersons()->detach();

            $business->delete();

            return $this->successResponse(null, 'Follow-up business deleted successfully');
        }, 'Follow-up business deletion', ['business_id' => $id]);
    }
}
