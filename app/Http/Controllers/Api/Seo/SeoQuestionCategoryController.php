<?php

namespace App\Http\Controllers\Api\Seo;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\SeoQuestionCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SeoQuestionCategoryController extends BaseApiController
{
    /**
     * Get all SEO question categories
     */
    public function index(Request $request): JsonResponse
    {
        $query = SeoQuestionCategory::query();

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $categories = $query->orderBy('name', 'asc')->get();

        return $this->successResponse($categories, 'SEO question categories retrieved successfully');
    }

    /**
     * Get active SEO question categories
     */
    public function getActive(): JsonResponse
    {
        $categories = SeoQuestionCategory::where('is_active', true)
            ->orderBy('name', 'asc')
            ->get();

        return $this->successResponse($categories, 'Active SEO question categories retrieved successfully');
    }

    /**
     * Get a specific SEO question category
     */
    public function show(int $id): JsonResponse
    {
        $category = SeoQuestionCategory::find($id);

        if (!$category) {
            return $this->errorResponse('SEO question category not found', 404);
        }

        return $this->successResponse($category, 'SEO question category retrieved successfully');
    }

    /**
     * Create a new SEO question category
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:seo_question_categories,name',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $category = SeoQuestionCategory::create([
            'name' => $request->name,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return $this->successResponse($category, 'SEO question category created successfully', 201);
    }

    /**
     * Update a SEO question category
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = SeoQuestionCategory::find($id);

        if (!$category) {
            return $this->errorResponse('SEO question category not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:seo_question_categories,name,' . $id,
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $updateData = ['name' => $request->name];

        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->boolean('is_active');
        }

        $category->update($updateData);

        return $this->successResponse($category, 'SEO question category updated successfully');
    }

    /**
     * Delete a SEO question category
     */
    public function destroy(int $id): JsonResponse
    {
        $category = SeoQuestionCategory::find($id);

        if (!$category) {
            return $this->errorResponse('SEO question category not found', 404);
        }

        $category->delete();

        return $this->successResponse(null, 'SEO question category deleted successfully');
    }
}
