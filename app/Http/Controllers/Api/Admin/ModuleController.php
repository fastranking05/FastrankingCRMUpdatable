<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ModuleController extends BaseApiController
{
    /**
     * Display a listing of modules.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->executeTransaction(function () use ($request) {
            $query = Module::with(['creator:id,first_name,last_name', 'roles:id,name']);

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
            $modules = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return $this->successResponse($modules, 'Modules retrieved successfully');
        }, 'Fetch modules list', ['filters' => $request->all()]);
    }

    /**
     * Store a newly created module.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:modules,name',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            $data = $request->only(['name', 'description', 'status']);
            $data['created_by'] = auth()->id();
            $data['status'] = $data['status'] ?? 'active';

            $module = Module::create($data);
            $module->load('creator:id,first_name,last_name');

            return $this->successResponse($module, 'Module created successfully', 201);
        }, 'Create module', $request->only(['name', 'status']));
    }

    /**
     * Display the specified module.
     */
    public function show(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $module = Module::with(['creator:id,first_name,last_name', 'roles:id,name'])
                ->find($id);

            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }

            return $this->successResponse($module, 'Module retrieved successfully');
        }, 'Fetch module', ['module_id' => $id]);
    }

    /**
     * Update the specified module.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:modules,name,' . $id,
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $id) {
            $module = Module::find($id);

            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }

            $data = $request->only(['name', 'description', 'status']);
            $module->update($data);

            $module->load(['creator:id,first_name,last_name', 'roles:id,name']);

            return $this->successResponse($module, 'Module updated successfully');
        }, 'Update module', ['module_id' => $id]);
    }

    /**
     * Remove the specified module.
     */
    public function destroy(string $id): JsonResponse
    {
        return $this->executeTransaction(function () use ($id) {
            $module = Module::find($id);

            if (!$module) {
                return $this->errorResponse('Module not found', 404);
            }

            // Detach all roles before deleting
            $module->roles()->detach();

            $module->delete();

            return $this->successResponse(null, 'Module deleted successfully');
        }, 'Delete module', ['module_id' => $id]);
    }
}
