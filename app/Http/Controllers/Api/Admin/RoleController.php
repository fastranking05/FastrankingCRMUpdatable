<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends BaseApiController
{
    /**
     * Display a listing of roles.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = Role::with(['creator:id,first_name,last_name', 'users:id,first_name,last_name', 'modules:id,name']);

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
            $roles = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->successResponse($roles, 'Roles retrieved successfully');
        }, 'Fetch roles list', ['filters' => $request->all()]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'module_permissions' => 'nullable|array',
            'module_permissions.*.module_id' => 'required_with:module_permissions|exists:modules,id',
            'module_permissions.*.can_create' => 'nullable|boolean',
            'module_permissions.*.can_read' => 'nullable|boolean',
            'module_permissions.*.can_update' => 'nullable|boolean',
            'module_permissions.*.can_delete' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $data = $request->only(['name', 'description', 'status']);
            $data['created_by'] = auth()->id();
            $data['status'] = $data['status'] ?? 'active';

            $role = Role::create($data);

            // Attach users if provided
            if ($request->has('user_ids')) {
                $role->users()->attach($request->user_ids);
            }

            // Attach modules with permissions if provided
            if ($request->has('module_permissions')) {
                foreach ($request->module_permissions as $permission) {
                    $role->modules()->attach($permission['module_id'], [
                        'can_create' => $permission['can_create'] ?? false,
                        'can_read' => $permission['can_read'] ?? false,
                        'can_update' => $permission['can_update'] ?? false,
                        'can_delete' => $permission['can_delete'] ?? false,
                    ]);
                }
            }

            $role->load(['creator:id,first_name,last_name', 'users:id,first_name,last_name', 'modules:id,name']);

            return $this->successResponse($role, 'Role created successfully', 201);
        }, 'Create role', $request->only(['name', 'status']));
    }

    /**
     * Display the specified role.
     */
    public function show(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $role = Role::with(['creator:id,first_name,last_name', 'users:id,first_name,last_name', 'modules:id,name'])
                ->find($id);

            if (!$role) {
                return $this->errorResponse('Role not found', 404);
            }

            return $this->successResponse($role, 'Role retrieved successfully');
        }, 'Fetch role', ['role_id' => $id]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:roles,name,' . $id,
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'module_permissions' => 'nullable|array',
            'module_permissions.*.module_id' => 'required_with:module_permissions|exists:modules,id',
            'module_permissions.*.can_create' => 'nullable|boolean',
            'module_permissions.*.can_read' => 'nullable|boolean',
            'module_permissions.*.can_update' => 'nullable|boolean',
            'module_permissions.*.can_delete' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $id) {
            $role = Role::find($id);

            if (!$role) {
                return $this->errorResponse('Role not found', 404);
            }

            $data = $request->only(['name', 'description', 'status']);
            $role->update($data);

            // Sync users if provided
            if ($request->has('user_ids')) {
                $role->users()->sync($request->user_ids);
            }

            // Sync modules with permissions if provided
            if ($request->has('module_permissions')) {
                $syncData = [];
                foreach ($request->module_permissions as $permission) {
                    $syncData[$permission['module_id']] = [
                        'can_create' => $permission['can_create'] ?? false,
                        'can_read' => $permission['can_read'] ?? false,
                        'can_update' => $permission['can_update'] ?? false,
                        'can_delete' => $permission['can_delete'] ?? false,
                    ];
                }
                $role->modules()->sync($syncData);
            }

            $role->load(['creator:id,first_name,last_name', 'users:id,first_name,last_name', 'modules:id,name']);

            return $this->successResponse($role, 'Role updated successfully');
        }, 'Update role', ['role_id' => $id]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $role = Role::find($id);

            if (!$role) {
                return $this->errorResponse('Role not found', 404);
            }

            // Detach all users and modules before deleting
            $role->users()->detach();
            $role->modules()->detach();

            $role->delete();

            return $this->successResponse(null, 'Role deleted successfully');
        }, 'Delete role', ['role_id' => $id]);
    }
}
