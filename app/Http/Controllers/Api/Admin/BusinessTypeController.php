<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\BusinessType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessTypeController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $types = BusinessType::with('creator:id,first_name,last_name')
            ->orderBy('name', 'asc')
            ->get();

        return $this->successResponse($types, 'Business types retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:business_types,name',
            'description' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $type = BusinessType::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active,
            'created_by' => auth()->id(),
        ]);

        $type->load('creator:id,first_name,last_name');

        return $this->successResponse($type, 'Business type created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $type = BusinessType::with('creator:id,first_name,last_name')
            ->findOrFail($id);

        return $this->successResponse($type, 'Business type retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $type = BusinessType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:business_types,name,' . $id,
            'description' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $type->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active,
        ]);

        $type->load('creator:id,first_name,last_name');

        return $this->successResponse($type, 'Business type updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $type = BusinessType::findOrFail($id);
        $type->delete();

        return $this->successResponse(null, 'Business type deleted successfully');
    }
}
