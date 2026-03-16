<?php

namespace App\Http\Controllers\Api\Followup;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\FollowupAuthPerson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FollowupAuthPersonController extends BaseApiController
{
    /**
     * Display a listing of follow-up authorized persons.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = FollowupAuthPerson::with(['creator:id,first_name,last_name', 'businesses']);

            // Filter by is_primary
            if ($request->has('is_primary')) {
                $query->where('is_primary', $request->boolean('is_primary'));
            }

            // Filter by gender
            if ($request->has('gender')) {
                $query->where('gender', $request->gender);
            }

            // Filter by name (first_name or last_name)
            if ($request->has('name')) {
                $search = $request->name;
                $query->where(function ($q) use ($search) {
                    $q->where('firstname', 'like', '%' . $search . '%')
                      ->orWhere('lastname', 'like', '%' . $search . '%');
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $persons = $query->paginate($perPage);

            return $this->successResponse($persons, 'Follow-up authorized persons retrieved successfully');
        }, 'Follow-up authorized person list retrieval');
    }

    /**
     * Store a newly created follow-up authorized person.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:50',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'required|string|max:255',
            'is_primary' => 'nullable|boolean',
            'designation' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'primaryphone' => 'nullable|string|unique:followup_auth_persons,primaryphone',
            'altphone' => 'nullable|string|unique:followup_auth_persons,altphone',
            'primarymobile' => 'nullable|string|unique:followup_auth_persons,primarymobile',
            'altmobile' => 'nullable|string|unique:followup_auth_persons,altmobile',
            'primaryemail' => 'required|email|unique:followup_auth_persons,primaryemail',
            'altemail' => 'nullable|email|unique:followup_auth_persons,altemail',
            'business_ids' => 'nullable|array',
            'business_ids.*' => 'exists:followup_businesses,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $data = $request->all();
            $data['created_by'] = auth()->id();

            $person = FollowupAuthPerson::create($data);

            // Attach businesses if provided
            if ($request->has('business_ids')) {
                $person->businesses()->attach($request->business_ids);
            }

            $person->load(['creator:id,first_name,last_name', 'businesses']);

            return $this->successResponse($person, 'Follow-up authorized person created successfully', 201);
        }, 'Follow-up authorized person creation', $request->only(['firstname', 'lastname', 'primaryemail']));
    }

    /**
     * Display the specified follow-up authorized person.
     */
    public function show(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $person = FollowupAuthPerson::with(['creator:id,first_name,last_name', 'businesses'])->find($id);

            if (!$person) {
                return $this->errorResponse('Follow-up authorized person not found', 404);
            }

            return $this->successResponse($person, 'Follow-up authorized person retrieved successfully');
        }, 'Follow-up authorized person retrieval', ['person_id' => $id]);
    }

    /**
     * Update the specified follow-up authorized person.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $person = FollowupAuthPerson::find($id);

        if (!$person) {
            return $this->errorResponse('Follow-up authorized person not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:50',
            'firstname' => 'sometimes|required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'lastname' => 'sometimes|required|string|max:255',
            'is_primary' => 'nullable|boolean',
            'designation' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'dob' => 'nullable|date',
            'primaryphone' => 'nullable|string|unique:followup_auth_persons,primaryphone,' . $id,
            'altphone' => 'nullable|string|unique:followup_auth_persons,altphone,' . $id,
            'primarymobile' => 'nullable|string|unique:followup_auth_persons,primarymobile,' . $id,
            'altmobile' => 'nullable|string|unique:followup_auth_persons,altmobile,' . $id,
            'primaryemail' => 'sometimes|required|email|unique:followup_auth_persons,primaryemail,' . $id,
            'altemail' => 'nullable|email|unique:followup_auth_persons,altemail,' . $id,
            'business_ids' => 'nullable|array',
            'business_ids.*' => 'exists:followup_businesses,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $person) {
            $person->update($request->all());

            // Sync businesses if provided
            if ($request->has('business_ids')) {
                $person->businesses()->sync($request->business_ids);
            }

            $person->load(['creator:id,first_name,last_name', 'businesses']);

            return $this->successResponse($person, 'Follow-up authorized person updated successfully');
        }, 'Follow-up authorized person update', ['person_id' => $person->id]);
    }

    /**
     * Remove the specified follow-up authorized person.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $person = FollowupAuthPerson::find($id);

            if (!$person) {
                return $this->errorResponse('Follow-up authorized person not found', 404);
            }

            // Detach all relationships
            $person->businesses()->detach();

            $person->delete();

            return $this->successResponse(null, 'Follow-up authorized person deleted successfully');
        }, 'Follow-up authorized person deletion', ['person_id' => $id]);
    }
}
