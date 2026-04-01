<?php

namespace App\Http\Controllers\Api\Quality;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\QualityQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QualityQuestionController extends BaseApiController
{
    /**
     * Get all quality questions
     */
    public function index(Request $request): JsonResponse
    {
        $query = QualityQuestion::with('creator:id,first_name,last_name');

        // Filter by active status if provided
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('question', 'like', '%' . $search . '%');
        }

        $questions = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 50));

        return $this->successResponse($questions, 'Quality questions retrieved successfully');
    }

    /**
     * Get a specific quality question
     */
    public function show(int $id): JsonResponse
    {
        $question = QualityQuestion::with('creator:id,first_name,last_name')
            ->find($id);

        if (!$question) {
            return $this->errorResponse('Quality question not found', 404);
        }

        return $this->successResponse($question, 'Quality question retrieved successfully');
    }

    /**
     * Create a new quality question
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:1000',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $question = QualityQuestion::create([
            'question' => $request->question,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'created_by' => auth()->id(),
        ]);

        // Load relationships for response
        $question->load('creator:id,first_name,last_name');

        return $this->successResponse($question, 'Quality question created successfully', 201);
    }

    /**
     * Update a quality question
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string|max:1000',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $question = QualityQuestion::find($id);
        if (!$question) {
            return $this->errorResponse('Quality question not found', 404);
        }

        $updateData = [
            'question' => $request->question,
        ];

        // Only update is_active if it's provided in the request
        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->boolean('is_active');
        }

        $question->update($updateData);

        // Load relationships for response
        $question->load('creator:id,first_name,last_name');

        return $this->successResponse($question, 'Quality question updated successfully');
    }

    /**
     * Delete a quality question
     */
    public function destroy(int $id): JsonResponse
    {
        $question = QualityQuestion::find($id);
        if (!$question) {
            return $this->errorResponse('Quality question not found', 404);
        }

        // Check if question has answers (prevent deletion if it has answers)
        // Note: This will need to be implemented when QualityAnswer model exists
        // For now, we'll allow deletion
        $question->delete();

        return $this->successResponse(null, 'Quality question deleted successfully');
    }

    /**
     * Toggle question active status
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $question = QualityQuestion::find($id);
        if (!$question) {
            return $this->errorResponse('Quality question not found', 404);
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
        $questions = QualityQuestion::where('is_active', true)
            ->with('creator:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse($questions, 'Active quality questions retrieved successfully');
    }
}
