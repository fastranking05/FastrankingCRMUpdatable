<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeamController extends BaseApiController
{
    /**
     * Display a listing of teams.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = Team::with(['creator:id,first_name,last_name', 'users:id,first_name,last_name,email']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by name
            if ($request->has('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $teams = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->successResponse($teams, 'Teams retrieved successfully');
        }, 'Fetch teams list', ['filters' => $request->all()]);
    }

    /**
     * Store a newly created team.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:teams,name',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $data = $request->only(['name', 'description', 'status']);
            $data['created_by'] = auth()->id();
            $data['status'] = $data['status'] ?? 'active';

            $team = Team::create($data);

            // Attach users if provided
            if ($request->has('user_ids')) {
                $team->users()->attach($request->user_ids);
            }

            $team->load(['creator:id,first_name,last_name', 'users:id,first_name,last_name,email']);

            return $this->successResponse($team, 'Team created successfully', 201);
        }, 'Create team', $request->only(['name', 'status']));
    }

    /**
     * Display the specified team.
     */
    public function show(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $team = Team::with(['creator:id,first_name,last_name', 'users:id,first_name,last_name,email'])
                ->find($id);

            if (!$team) {
                return $this->errorResponse('Team not found', 404);
            }

            return $this->successResponse($team, 'Team retrieved successfully');
        }, 'Fetch team', ['team_id' => $id]);
    }

    /**
     * Update the specified team.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:teams,name,' . $id,
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $id) {
            $team = Team::find($id);

            if (!$team) {
                return $this->errorResponse('Team not found', 404);
            }

            $data = $request->only(['name', 'description', 'status']);
            $team->update($data);

            // Sync users if provided
            if ($request->has('user_ids')) {
                $team->users()->sync($request->user_ids);
            }

            $team->load(['creator:id,first_name,last_name', 'users:id,first_name,last_name,email']);

            return $this->successResponse($team, 'Team updated successfully');
        }, 'Update team', ['team_id' => $id]);
    }

    /**
     * Remove the specified team.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $team = Team::find($id);

            if (!$team) {
                return $this->errorResponse('Team not found', 404);
            }

            // Detach all users before deleting
            $team->users()->detach();

            $team->delete();

            return $this->successResponse(null, 'Team deleted successfully');
        }, 'Delete team', ['team_id' => $id]);
    }
}
