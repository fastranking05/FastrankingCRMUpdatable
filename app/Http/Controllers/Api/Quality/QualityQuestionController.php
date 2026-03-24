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
    public function index(): JsonResponse
    {
        $questions = QualityQuestion::with('creator:id,first_name,last_name')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->successResponse($questions, 'Quality questions retrieved successfully');
    }

    /**
     * Create a new quality question
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $question = QualityQuestion::create([
            'question' => $request->question,
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse($question, 'Quality question created successfully', 201);
    }

    /**
     * Update a quality question
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $question = QualityQuestion::find($id);
        if (!$question) {
            return $this->errorResponse('Quality question not found', 404);
        }

        $question->update(['question' => $request->question]);

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

        // Check if question has answers
        if ($question->answers()->exists()) {
            return $this->errorResponse('Cannot delete question with existing answers', 400);
        }

        $question->delete();

        return $this->successResponse(null, 'Quality question deleted successfully');
    }
}
