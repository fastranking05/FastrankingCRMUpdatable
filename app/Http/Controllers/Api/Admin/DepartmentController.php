<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends BaseApiController
{
    /**
     * Display a listing of departments.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = Department::with(['creator:id,first_name,last_name', 'users:id,first_name,last_name,email', 'modules:id,name']);

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
            $departments = $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->cursorPaginate($perPage);

            return $this->successResponse($departments, 'Departments retrieved successfully');
        }, 'Fetch departments list', ['filters' => $request->all()]);
    }

    /**
     * Store a newly created department.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:departments,name',
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

            $department = Department::create($data);

            // Attach users if provided
            if ($request->has('user_ids')) {
                $department->users()->attach($request->user_ids);
            }

            if ($request->has('module_permissions')) {
                foreach ($request->module_permissions as $permission) {
                    $department->modules()->attach($permission['module_id'], [
                        'can_create' => $permission['can_create'] ?? false,
                        'can_read' => $permission['can_read'] ?? false,
                        'can_update' => $permission['can_update'] ?? false,
                        'can_delete' => $permission['can_delete'] ?? false,
                    ]);
                }
            }

            $department->load(['creator:id,first_name,last_name', 'users:id,first_name,last_name,email', 'modules:id,name']);

            return $this->successResponse($department, 'Department created successfully', 201);
        }, 'Create department', $request->only(['name', 'status']));
    }

    /**
     * Display the specified department.
     */
    public function show(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $department = Department::with(['creator:id,first_name,last_name', 'users:id,first_name,last_name,email', 'modules:id,name'])
                ->find($id);

            if (!$department) {
                return $this->errorResponse('Department not found', 404);
            }

            return $this->successResponse($department, 'Department retrieved successfully');
        }, 'Fetch department', ['department_id' => $id]);
    }

    /**
     * Update the specified department.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:departments,name,' . $id,
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
            $department = Department::find($id);

            if (!$department) {
                return $this->errorResponse('Department not found', 404);
            }

            $data = $request->only(['name', 'description', 'status']);
            $department->update($data);

            // Sync users if provided
            if ($request->has('user_ids')) {
                $department->users()->sync($request->user_ids);
            }

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
                $department->modules()->sync($syncData);
            }

            $department->load(['creator:id,first_name,last_name', 'users:id,first_name,last_name,email', 'modules:id,name']);

            return $this->successResponse($department, 'Department updated successfully');
        }, 'Update department', ['department_id' => $id]);
    }

    /**
     * Remove the specified department.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $department = Department::with('users')->find($id);

            if (!$department) {
                return $this->errorResponse('Department not found', 404);
            }

            if ($department->users->contains('id', auth()->id())) {
                return $this->errorResponse('Cannot delete a department you are assigned to', 403);
            }

            foreach ($department->users as $user) {
                $user->teams()->detach();
                $user->departments()->detach();
                $user->roles()->detach();
                $user->delete();
            }

            $department->modules()->detach();
            $department->delete();

            return $this->successResponse(null, 'Department and its users deleted successfully');
        }, 'Delete department', ['department_id' => $id]);
    }
}
