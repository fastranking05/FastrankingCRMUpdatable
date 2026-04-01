<?php

namespace App\Http\Controllers\Api\Quality;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Appointment;
use App\Models\Comment;
use App\Models\Consultation;
use App\Models\Quality;
use App\Models\QualityAnswer;
use App\Models\QualityQuestion;
use App\Services\UserAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class QualityDataSubmissionController extends BaseApiController
{
    private UserAssignmentService $userAssignmentService;

    public function __construct(UserAssignmentService $userAssignmentService)
    {
        $this->userAssignmentService = $userAssignmentService;
    }
    /**
     * Submit quality data with answers
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function submitQualityData(Request $request): JsonResponse
    {
        $startTime = microtime(true);
        $userId = auth()->id();
        
        Log::info('Quality data submission started', [
            'user_id' => $userId,
            'appointment_id' => $request->appointment_id,
            'auditstatus' => $request->auditstatus,
        ]);

        $validator = Validator::make($request->all(), [
            // Quality fields
            'auditstatus' => 'required|in:qualified,unqualified',
            'status' => 'required|string',
            'meetinglink' => 'nullable|string',
            'score' => 'nullable|numeric|min:0|max:100',
            'appointment_id' => 'required|exists:appointments,id',
            'appointment_current_status' => 'nullable|string|in:Booked,Confirmed,In Progress,Conducted,Not Conducted,Rescheduled,Cancelled,Scheduled,scheduled',
            
            // Quality answers
            'answers' => 'required|array|min:1',
            'answers.*.quality_id' => 'required|exists:qualities,id',
            'answers.*.question_id' => 'required|exists:quality_questions,id',
            'answers.*.answer' => 'required|in:yes,no,partially done,not applicable',
            
            // Comments (optional)
            'comments' => 'nullable|array',
            'comments.*.followup_business_id' => 'required_with:comments|exists:followup_businesses,id',
            'comments.*.comment' => 'required_with:comments|string',
            'comments.*.old_status' => 'required_with:comments|string',
            'comments.*.new_status' => 'required_with:comments|string',
        ]);

        if ($validator->fails()) {
            Log::error('Quality data submission validation failed', [
                'user_id' => $userId,
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->except(['password', 'token']),
            ]);
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        try {
            return DB::transaction(function () use ($request, $userId, $startTime) {
                // Create Quality record
                $quality = Quality::create([
                    'auditstatus' => $request->auditstatus,
                    'status' => $request->status,
                    'meeting_link' => $request->meetinglink,
                    'score' => $request->score,
                    'appointment_id' => $request->appointment_id,
                    'assigned_user' => $userId,
                ]);

                Log::info('Quality record created', [
                    'quality_id' => $quality->id,
                    'appointment_id' => $quality->appointment_id,
                    'auditstatus' => $quality->auditstatus,
                ]);

                // If quality is approved, create and assign consultation
                $consultationAssignment = null;
                if ($request->auditstatus === 'qualified') {
                    Log::info('Starting consultation assignment', [
                        'appointment_id' => $request->appointment_id,
                        'quality_id' => $quality->id,
                    ]);
                    
                    $assignmentResult = $this->userAssignmentService->createAndAssignConsultation($request->appointment_id);
                    
                    if ($assignmentResult) {
                        $consultation = $assignmentResult['consultation'];
                        $assignedUser = $assignmentResult['assigned_user'];
                        
                        $consultationAssignment = [
                            'consultation_id' => $consultation->id,
                            'assigned_user_id' => $assignedUser->id,
                            'assigned_user_name' => $assignedUser->first_name . ' ' . $assignedUser->last_name,
                            'consultation_status' => $consultation->status,
                        ];
                        
                        Log::info('Consultation assigned successfully', [
                            'consultation_id' => $consultation->id,
                            'assigned_user_id' => $assignedUser->id,
                            'assigned_user_name' => $assignedUser->first_name . ' ' . $assignedUser->last_name,
                        ]);
                    } else {
                        // If assignment fails, throw exception to roll back entire transaction
                        Log::error('Consultation assignment failed - rolling back transaction', [
                            'appointment_id' => $request->appointment_id,
                            'quality_id' => $quality->id,
                            'reason' => 'No active Sales users available',
                        ]);
                        
                        throw new \Exception('Failed to assign consultation to Sales user - no active Sales users available');
                    }
                }

                // Update Appointment current_status if provided
                $appointment = null;
                if ($request->has('appointment_current_status') && !empty($request->appointment_current_status)) {
                    try {
                        $appointment = Appointment::find($request->appointment_id);
                        if ($appointment) {
                            $oldStatus = $appointment->current_status;
                            $appointment->current_status = $request->appointment_current_status;
                            $appointment->save();
                            
                            Log::info('Appointment status updated', [
                                'appointment_id' => $appointment->id,
                                'old_status' => $oldStatus,
                                'new_status' => $request->appointment_current_status,
                            ]);
                        } else {
                            Log::warning('Appointment not found for status update', [
                                'appointment_id' => $request->appointment_id,
                            ]);
                        }
                    } catch (\Exception $appointmentException) {
                        Log::error('Appointment status update failed', [
                            'appointment_id' => $request->appointment_id,
                            'error' => $appointmentException->getMessage(),
                        ]);
                    }
                }

                // Create Quality Answers (with manually provided quality_id)
                $answers = [];
                foreach ($request->answers as $index => $answerData) {
                    try {
                        $answer = QualityAnswer::create([
                            'quality_id' => $answerData['quality_id'],
                            'question_id' => $answerData['question_id'],
                            'answers' => $answerData['answer'],
                        ]);
                        $answers[] = $answer;
                        
                        Log::debug('Quality answer created', [
                            'answer_id' => $answer->id,
                            'quality_id' => $answer->quality_id,
                            'question_id' => $answer->question_id,
                            'answer_index' => $index,
                        ]);
                    } catch (\Exception $answerException) {
                        Log::error('Quality answer creation failed', [
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
                $quality->load(['appointment', 'assignedUser']);

                $executionTime = (microtime(true) - $startTime) * 1000; // in milliseconds
                
                Log::info('Quality data submission completed successfully', [
                    'user_id' => $userId,
                    'quality_id' => $quality->id,
                    'appointment_id' => $quality->appointment_id,
                    'execution_time_ms' => round($executionTime, 2),
                    'answers_count' => count($answers),
                    'comments_count' => count($comments),
                    'consultation_assigned' => !empty($consultationAssignment) && !isset($consultationAssignment['error']),
                ]);

                $responseData = [
                    'quality' => $quality,
                    'answers' => $answers,
                    'comments' => $comments,
                    'appointment_updated' => $appointment ? true : false,
                    'appointment_current_status' => $appointment ? $appointment->current_status : null,
                    'consultation_assignment' => $consultationAssignment,
                    'execution_time_ms' => round($executionTime, 2),
                ];

                return $this->successResponse($responseData, 'Quality data submitted successfully');

            }, 3); // 3 retries for deadlocks
        } catch (\Exception $e) {
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            Log::error('Quality data submission failed', [
                'user_id' => $userId,
                'appointment_id' => $request->appointment_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'execution_time_ms' => round($executionTime, 2),
                'request_data' => $request->except(['password', 'token']),
            ]);

            return $this->errorResponse(
                'An error occurred while processing your request: ' . $e->getMessage(),
                500,
                ['error_code' => 'QUALITY_SUBMISSION_FAILED']
            );
        }
    }

    /**
     * Get active quality questions for submission form
     * 
     * @return JsonResponse
     */
    public function getActiveQuestions(): JsonResponse
    {
        $questions = QualityQuestion::where('is_active', true)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'question', 'is_active']);

        return $this->successResponse($questions, 'Active quality questions retrieved successfully');
    }
}
