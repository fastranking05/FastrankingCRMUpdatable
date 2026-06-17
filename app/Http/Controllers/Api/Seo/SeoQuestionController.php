<?php

namespace App\Http\Controllers\Api\Seo;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\SeoQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SeoQuestionController extends BaseApiController
{
    /**
     * Get all SEO questions
     */
    public function index(Request $request): JsonResponse
    {
        $query = SeoQuestion::with([
            'creator:id,first_name,last_name',
            'category:id,name,is_active',
        ]);

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('seo_question_category_id')) {
            $query->where('seo_question_category_id', $request->seo_question_category_id);
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', '%' . $search . '%');
        }

        $questions = $query->orderByDesc('seo_questions.created_at')
            ->orderByDesc('seo_questions.id')
            ->cursorPaginate($request->get('per_page', 50));

        return $this->successResponse($questions, 'SEO questions retrieved successfully');
    }

    /**
     * Get a specific SEO question
     */
    public function show(int $id): JsonResponse
    {
        $question = SeoQuestion::with([
            'creator:id,first_name,last_name',
            'category:id,name,is_active',
        ])->find($id);

        if (!$question) {
            return $this->errorResponse('SEO question not found', 404);
        }

        return $this->successResponse($question, 'SEO question retrieved successfully');
    }

    /**
     * Create a new SEO question
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:1000',
            'seo_question_category_id' => 'nullable|integer|exists:seo_question_categories,id',
            'answer_type' => 'required|string|in:text,textarea,number,date,dropdown',
            'dropdown_options' => 'required_if:answer_type,dropdown|array',
            'dropdown_options.*' => 'string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $question = SeoQuestion::create([
            'name' => $request->name,
            'seo_question_category_id' => $request->seo_question_category_id,
            'answer_type' => $request->answer_type,
            'dropdown_options' => $request->answer_type === 'dropdown' ? $request->dropdown_options : null,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'created_by' => auth()->id(),
        ]);

        $question->load([
            'creator:id,first_name,last_name',
            'category:id,name,is_active',
        ]);

        return $this->successResponse($question, 'SEO question created successfully', 201);
    }

    /**
     * Update a SEO question
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:1000',
            'seo_question_category_id' => 'nullable|integer|exists:seo_question_categories,id',
            'answer_type' => 'required|string|in:text,textarea,number,date,dropdown',
            'dropdown_options' => 'required_if:answer_type,dropdown|array',
            'dropdown_options.*' => 'string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $question = SeoQuestion::find($id);
        if (!$question) {
            return $this->errorResponse('SEO question not found', 404);
        }

        $updateData = [
            'name' => $request->name,
            'answer_type' => $request->answer_type,
            'dropdown_options' => $request->answer_type === 'dropdown' ? $request->dropdown_options : null,
        ];

        if ($request->has('seo_question_category_id')) {
            $updateData['seo_question_category_id'] = $request->seo_question_category_id;
        }

        // Only update is_active if it's provided in the request
        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->boolean('is_active');
        }

        $question->update($updateData);

        $question->load([
            'creator:id,first_name,last_name',
            'category:id,name,is_active',
        ]);

        return $this->successResponse($question, 'SEO question updated successfully');
    }

    /**
     * Delete a SEO question
     */
    public function destroy(int $id): JsonResponse
    {
        $question = SeoQuestion::find($id);
        if (!$question) {
            return $this->errorResponse('SEO question not found', 404);
        }

        $question->delete();

        return $this->successResponse(null, 'SEO question deleted successfully');
    }

    /**
     * Toggle question active status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $question = SeoQuestion::find($id);
        if (!$question) {
            return $this->errorResponse('SEO question not found', 404);
        }

        $question->update(['is_active' => !$question->is_active]);

        return $this->successResponse(
            ['is_active' => $question->is_active],
            'Question status updated successfully'
        );
    }

    /**
     * Get active questions only
     */
    public function getActive(): JsonResponse
    {
        $questions = SeoQuestion::where('is_active', true)
            ->with([
                'creator:id,first_name,last_name',
                'category:id,name,is_active',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse($questions, 'Active SEO questions retrieved successfully');
    }
}
