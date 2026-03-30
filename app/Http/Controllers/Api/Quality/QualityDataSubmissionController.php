<?php

namespace App\Http\Controllers\Api\Quality;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Appointment;
use App\Models\Comment;
use App\Models\Quality;
use App\Models\QualityAnswer;
use App\Models\QualityQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QualityDataSubmissionController extends BaseApiController
{
    /**
     * Submit quality data with answers
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function submitQualityData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Quality fields
            'auditstatus' => 'required|in:qualified,unqualified',
            'status' => 'required|string',
            'meetinglink' => 'nullable|string',
            'score' => 'nullable|numeric|min:0|max:100',
            'appointment_id' => 'required|exists:appointments,id',
            'appointment_current_status' => 'nullable|string|in:Booked,Confirmed,In Progress,Conducted,Not Conducted,Rescheduled,Cancelled',
            
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
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request) {
            // Create Quality record
            $quality = Quality::create([
                'auditstatus' => $request->auditstatus,
                'status' => $request->status,
                'meeting_link' => $request->meetinglink,
                'score' => $request->score,
                'appointment_id' => $request->appointment_id,
                'assigned_user' => auth()->id(),
            ]);

            // Update Appointment current_status if provided
            $appointment = null;
            if ($request->has('appointment_current_status') && !empty($request->appointment_current_status)) {
                $appointment = Appointment::find($request->appointment_id);
                if ($appointment) {
                    $appointment->current_status = $request->appointment_current_status;
                    $appointment->save();
                }
            }

            // Create Quality Answers (with manually provided quality_id)
            $answers = [];
            foreach ($request->answers as $answerData) {
                $answer = QualityAnswer::create([
                    'quality_id' => $answerData['quality_id'],
                    'question_id' => $answerData['question_id'],
                    'answers' => $answerData['answer'],
                ]);
                $answers[] = $answer;
            }

            // Create Comments (with manually provided followup_business_id)
            $comments = [];
            if ($request->has('comments') && is_array($request->comments)) {
                foreach ($request->comments as $commentData) {
                    $comment = Comment::create([
                        'followup_business_id' => $commentData['followup_business_id'],
                        'comment' => $commentData['comment'],
                        'old_status' => $commentData['old_status'],
                        'new_status' => $commentData['new_status'],
                        'created_by' => auth()->id(),
                    ]);
                    $comments[] = $comment;
                }
            }

            // Load relationships for response
            $quality->load([
                'assignedUser:id,first_name,last_name',
                'answers.question:id,question',
            ]);

            $responseData = [
                'quality' => $quality,
                'comments' => $comments,
                'appointment_updated' => $appointment ? true : false,
                'appointment_current_status' => $appointment ? $appointment->current_status : null,
            ];

            return $this->successResponse($responseData, 'Quality data submitted successfully', 201);
        }, 'Quality data submission');
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
