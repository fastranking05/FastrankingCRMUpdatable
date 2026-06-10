<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends BaseApiController
{
    /**
     * Display a listing of services.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::with('creator:id,first_name,last_name');

        if ($request->has('status')) {
            $query->where('status', $request->boolean('status'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $services = $query->orderBy('name', 'asc')->get();

        return $this->successResponse($services, 'Services retrieved successfully');
    }

    /**
     * Store a newly created service.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:services,name',
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $service = Service::create([
            'name' => $request->name,
            'status' => $request->boolean('status'),
            'created_by' => auth()->id(),
        ]);

        $service->load('creator:id,first_name,last_name');

        return $this->successResponse($service, 'Service created successfully', 201);
    }

    /**
     * Display the specified service.
     */
    public function show(int $id): JsonResponse
    {
        $service = Service::with('creator:id,first_name,last_name')->findOrFail($id);

        return $this->successResponse($service, 'Service retrieved successfully');
    }

    /**
     * Update the specified service.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $service = Service::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:services,name,' . $id,
            'status' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $service->update([
            'name' => $request->name,
            'status' => $request->boolean('status'),
        ]);

        $service->load('creator:id,first_name,last_name');

        return $this->successResponse($service, 'Service updated successfully');
    }

    /**
     * Remove the specified service.
     */
    public function destroy(int $id): JsonResponse
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return $this->successResponse(null, 'Service deleted successfully');
    }
}
