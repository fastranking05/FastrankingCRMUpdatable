<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends BaseApiController
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = User::with(['creator:id,first_name,last_name', 'teams', 'departments', 'roles']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by user_type
            if ($request->has('user_type')) {
                $query->where('user_type', $request->user_type);
            }

            // Filter by name (first_name or last_name)
            if ($request->has('name')) {
                $search = $request->name;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', '%' . $search . '%')
                      ->orWhere('last_name', 'like', '%' . $search . '%');
                });
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return $this->successResponse($users, 'Users retrieved successfully');
        }, 'User list retrieval');
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'dob' => 'required|date',
            'email' => 'nullable|email|unique:users,email',
            'mobile' => 'required|string|unique:users,mobile',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6|confirmed',
            'date_of_joining' => 'required|date',
            'emp_id' => 'required|string|unique:users,emp_id',
            'designation' => 'required|string|max:255',
            'user_type' => 'required|in:admin,manager,employee',
            'status' => 'nullable|in:active,inactive,suspended',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $data = $request->all();
            $data['password'] = Hash::make($request->password);
            $data['status'] = $data['status'] ?? 'active';
            $data['created_by'] = auth()->id();

            $user = User::create($data);

            // Attach teams if provided
            if ($request->has('team_ids')) {
                $user->teams()->attach($request->team_ids);
            }

            // Attach departments if provided
            if ($request->has('department_ids')) {
                $user->departments()->attach($request->department_ids);
            }

            // Attach roles if provided
            if ($request->has('role_ids')) {
                $user->roles()->attach($request->role_ids);
            }

            $user->load(['creator:id,first_name,last_name', 'teams', 'departments', 'roles']);

            return $this->successResponse($user->makeHidden(['password']), 'User created successfully', 201);
        }, 'User creation', $request->only(['username', 'email', 'user_type']));
    }

    /**
     * Display the specified user.
     */
    public function show(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $user = User::with([
                'creator:id,first_name,last_name', 
                'teams', 
                'departments', 
                'roles.modules' => function ($query) {
                    $query->select('modules.id', 'modules.name', 'modules.description', 'modules.status')
                          ->withPivot(['can_create', 'can_read', 'can_update', 'can_delete']);
                }, 
                'createdUsers:id,first_name,last_name'
            ])->find($id);

            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            // Format modules and permissions
            $modulesWithPermissions = [];
            foreach ($user->roles as $role) {
                foreach ($role->modules as $module) {
                    $moduleId = $module->id;
                    
                    if (!isset($modulesWithPermissions[$moduleId])) {
                        $modulesWithPermissions[$moduleId] = [
                            'id' => $module->id,
                            'name' => $module->name,
                            'description' => $module->description,
                            'status' => $module->status,
                            'permissions' => [
                                'can_create' => false,
                                'can_read' => false,
                                'can_update' => false,
                                'can_delete' => false
                            ]
                        ];
                    }
                    
                    // Merge permissions (union of all role permissions)
                    $modulesWithPermissions[$moduleId]['permissions']['can_create'] = $modulesWithPermissions[$moduleId]['permissions']['can_create'] || $module->pivot->can_create;
                    $modulesWithPermissions[$moduleId]['permissions']['can_read'] = $modulesWithPermissions[$moduleId]['permissions']['can_read'] || $module->pivot->can_read;
                    $modulesWithPermissions[$moduleId]['permissions']['can_update'] = $modulesWithPermissions[$moduleId]['permissions']['can_update'] || $module->pivot->can_update;
                    $modulesWithPermissions[$moduleId]['permissions']['can_delete'] = $modulesWithPermissions[$moduleId]['permissions']['can_delete'] || $module->pivot->can_delete;
                }
            }
            
            // Convert to array and sort by name
            $modulesWithPermissions = array_values($modulesWithPermissions);
            usort($modulesWithPermissions, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            
            // Add modules to user data
            $userData = $user->makeHidden(['password'])->toArray();
            $userData['modules'] = $modulesWithPermissions;

            return $this->successResponse($userData, 'User retrieved successfully');
        }, 'User retrieval', ['user_id' => $id]);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse('User not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'gender' => 'sometimes|required|in:male,female,other',
            'dob' => 'sometimes|required|date',
            'email' => 'nullable|email|unique:users,email,' . $id,
            'mobile' => 'sometimes|required|string|unique:users,mobile,' . $id,
            'username' => 'sometimes|required|string|unique:users,username,' . $id,
            'password' => 'nullable|string|min:6|confirmed',
            'date_of_joining' => 'sometimes|required|date',
            'emp_id' => 'sometimes|required|string|unique:users,emp_id,' . $id,
            'designation' => 'sometimes|required|string|max:255',
            'user_type' => 'sometimes|required|in:admin,manager,employee',
            'status' => 'nullable|in:active,inactive,suspended',
            'team_ids' => 'nullable|array',
            'team_ids.*' => 'exists:teams,id',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $user) {
            $data = $request->all();

            // Only hash password if provided
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            } else {
                unset($data['password']);
            }

            $user->update($data);

            // Sync teams if provided
            if ($request->has('team_ids')) {
                $user->teams()->sync($request->team_ids);
            }

            // Sync departments if provided
            if ($request->has('department_ids')) {
                $user->departments()->sync($request->department_ids);
            }

            // Sync roles if provided
            if ($request->has('role_ids')) {
                $user->roles()->sync($request->role_ids);
            }

            $user->load(['creator:id,first_name,last_name', 'teams', 'departments', 'roles']);

            return $this->successResponse($user->makeHidden(['password']), 'User updated successfully');
        }, 'User update', ['user_id' => $id]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(int $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $user = User::find($id);

            if (!$user) {
                return $this->errorResponse('User not found', 404);
            }

            // Prevent deleting yourself
            if ($user->id === auth()->id()) {
                return $this->errorResponse('Cannot delete your own account', 403);
            }

            // Detach all relationships
            $user->teams()->detach();
            $user->departments()->detach();
            $user->roles()->detach();

            $user->delete();

            return $this->successResponse(null, 'User deleted successfully');
        }, 'User deletion', ['user_id' => $id]);
    }
}
