<?php

namespace App\Http\Controllers\Api\Seo;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Comment;
use App\Models\SeoDetail;
use App\Models\SeoQuestion;
use App\Models\SeoQuestionAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SeoDataSubmissionController extends BaseApiController
{
    /**
     * Submit SEO audit form with answers
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function submitSeoAudit(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $userId = auth()->id();

        Log::info('SEO audit submission started', [
            'user_id' => $userId,
            'seo_detail_id' => $request->seo_detail_id,
            'status' => $request->status,
        ]);

        $validator = Validator::make($request->all(), [
            // SEO Detail fields
            'seo_detail_id' => 'required|exists:seo_details,id',
            'audited_website' => 'required|string|max:500',
            'audited_date' => 'required|date',
            'auditor' => 'required|string|max:255',
            'status' => 'required|string|in:Pending,Audit Completed,Not Applicable',
            'reason' => 'required_if:status,Not Applicable|nullable|string|max:2000',

            // SEO Question Answers
            'answers' => 'required|array|min:1',
            'answers.*.seo_question_id' => 'required|exists:seo_questions,id',
            'answers.*.answer' => 'required|string|max:5000',
            'answers.*.comments' => 'nullable|string|max:5000',

            // Comments (optional)
            'comments' => 'nullable|array',
            'comments.*.followup_business_id' => 'required_with:comments|exists:followup_businesses,id',
            'comments.*.comment' => 'required_with:comments|string',
            'comments.*.old_status' => 'required_with:comments|string',
            'comments.*.new_status' => 'required_with:comments|string',
        ]);

        if ($validator->fails()) {
            Log::error('SEO audit submission validation failed', [
                'user_id' => $userId,
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->except(['password', 'token']),
            ]);
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            return DB::transaction(function () use ($request, $userId, $startTime) {
                // Find and update the SEO Detail record
                $seoDetail = SeoDetail::find($request->seo_detail_id);

                if (!$seoDetail) {
                    Log::error('SEO detail not found', [
                        'seo_detail_id' => $request->seo_detail_id,
                    ]);
                    return $this->errorResponse('SEO detail record not found', 404);
                }

                // Update SEO Detail fields
                $seoDetail->update([
                    'audited_website' => $request->audited_website,
                    'audited_date' => $request->audited_date,
                    'auditor' => $request->auditor,
                    'status' => $request->status,
                    'reason' => $request->status === 'Not Applicable' ? $request->reason : null,
                ]);

                Log::info('SEO detail updated', [
                    'seo_detail_id' => $seoDetail->id,
                    'status' => $seoDetail->status,
                    'audited_website' => $seoDetail->audited_website,
                ]);

                // Delete existing answers for this SEO detail (in case of re-submission)
                $deletedCount = SeoQuestionAnswer::where('seo_details_id', $seoDetail->id)->delete();
                if ($deletedCount > 0) {
                    Log::info('Existing SEO answers deleted for re-submission', [
                        'seo_detail_id' => $seoDetail->id,
                        'deleted_count' => $deletedCount,
                    ]);
                }

                // Create new SEO Question Answers
                $answers = [];
                foreach ($request->answers as $index => $answerData) {
                    try {
                        $answer = SeoQuestionAnswer::create([
                            'seo_details_id' => $seoDetail->id,
                            'seo_question_id' => $answerData['seo_question_id'],
                            'answer' => $answerData['answer'],
                            'comments' => $answerData['comments'] ?? null,
                        ]);
                        $answers[] = $answer;

                        Log::debug('SEO answer created', [
                            'answer_id' => $answer->id,
                            'seo_detail_id' => $answer->seo_details_id,
                            'seo_question_id' => $answer->seo_question_id,
                            'answer_index' => $index,
                        ]);
                    } catch (\Exception $answerException) {
                        Log::error('SEO answer creation failed', [
                            'answer_index' => $index,
                            'answer_data' => $answerData,
                            'error' => $answerException->getMessage(),
                        ]);
                        throw $answerException; // Re-throw to trigger transaction rollback
                    }
                }

                // Create Comments (with manually provided followup_business_id)
                $comments = [];
                if ($request->has('comments') && is_array($request->comments)) {
                    foreach ($request->comments as $index => $commentData) {
                        try {
                            $comment = Comment::create([
                                'followup_business_id' => $commentData['followup_business_id'],
                                'comment' => $commentData['comment'],
                                'old_status' => $commentData['old_status'],
                                'new_status' => $commentData['new_status'],
                                'created_by' => $userId,
                            ]);
                            $comments[] = $comment;

                            Log::debug('Comment created', [
                                'comment_id' => $comment->id,
                                'followup_business_id' => $comment->followup_business_id,
                                'comment_index' => $index,
                            ]);
                        } catch (\Exception $commentException) {
                            Log::error('Comment creation failed', [
                                'comment_index' => $index,
                                'comment_data' => $commentData,
                                'error' => $commentException->getMessage(),
                            ]);
                            throw $commentException; // Re-throw to trigger transaction rollback
                        }
                    }
                }

                // Load relationships for response
                $seoDetail->load(['followupBusiness', 'assignedUser', 'questionAnswers.seoQuestion']);

                $executionTime = (microtime(true) - $startTime) * 1000; // in milliseconds

                Log::info('SEO audit submission completed successfully', [
                    'user_id' => $userId,
                    'seo_detail_id' => $seoDetail->id,
                    'execution_time_ms' => round($executionTime, 2),
                    'answers_count' => count($answers),
                    'comments_count' => count($comments),
                ]);

                $responseData = [
                    'seo_detail' => $seoDetail,
                    'answers' => $answers,
                    'comments' => $comments,
                    'execution_time_ms' => round($executionTime, 2),
                ];

                return $this->successResponse($responseData, 'SEO audit submitted successfully');

            }, 3); // 3 retries for deadlocks
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;

            Log::error('SEO audit submission failed', [
                'user_id' => $userId,
                'seo_detail_id' => $request->seo_detail_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_ms' => round($executionTime, 2),
                'request_data' => $request->except(['password', 'token']),
            ]);

            return $this->errorResponse(
                'An error occurred while processing your request: ' . $e->getMessage(),
                500,
                ['error_code' => 'SEO_SUBMISSION_FAILED']
            );
        }
    }

    /**
     * Get active SEO questions for the submission form
     *
     * @return JsonResponse
     */
    public function getActiveQuestions(): JsonResponse
    {
        $questions = SeoQuestion::where('is_active', true)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'name', 'answer_type', 'dropdown_options', 'is_active']);

        return $this->successResponse($questions, 'Active SEO questions retrieved successfully');
    }
}
