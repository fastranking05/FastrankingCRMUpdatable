<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\BusinessCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessCategoryController extends BaseApiController
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $categories = BusinessCategory::with('creator:id,first_name,last_name')
            ->orderBy('name', 'asc')
            ->get();

        return $this->successResponse($categories, 'Business categories retrieved successfully');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:business_categories,name',
            'description' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $category = BusinessCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active,
            'created_by' => auth()->id(),
        ]);

        $category->load('creator:id,first_name,last_name');

        return $this->successResponse($category, 'Business category created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $category = BusinessCategory::with('creator:id,first_name,last_name')
            ->findOrFail($id);

        return $this->successResponse($category, 'Business category retrieved successfully');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $category = BusinessCategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:business_categories,name,' . $id,
            'description' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->is_active,
        ]);

        $category->load('creator:id,first_name,last_name');

        return $this->successResponse($category, 'Business category updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $category = BusinessCategory::findOrFail($id);
        $category->delete();

        return $this->successResponse(null, 'Business category deleted successfully');
    }
}
