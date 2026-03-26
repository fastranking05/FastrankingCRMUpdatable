<?php

namespace App\Http\Controllers\Api\Quality;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Quality;
use App\Models\QualityAnswer;
use App\Models\QualityQuestion;
use App\Services\QualityAssignmentService;
use App\Services\DateRangeFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QualityController extends BaseApiController
{
    protected $assignmentService;
    protected $dateRangeFilterService;

    public function __construct(
        QualityAssignmentService $assignmentService,
        DateRangeFilterService $dateRangeFilterService
    ) {
        $this->assignmentService = $assignmentService;
        $this->dateRangeFilterService = $dateRangeFilterService;
    }

    /**
     * Get all quality records with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Quality::with([
            'appointment:id,date,followup_business_id',
            'appointment.business:id,name',
            'assignedUser:id,first_name,last_name',
            'answers:id,quality_id,question_id,answers',
        ]);

        // Apply flexible filters using DateRangeFilterService
        $query = $this->dateRangeFilterService->applyFilters($query, $request, [
            'date_column' => 'created_at',
            'user_column' => 'assigned_user',
            'status_column' => 'status',
            'search_columns' => ['appointment_id', 'appointment.business.name']
        ]);

        // Apply additional specific filters
        if ($request->has('auditstatus')) {
            $query->where('auditstatus', $request->auditstatus);
        }

        $qualities = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse($qualities, 'Quality records retrieved successfully');
    }

    /**
     * Get filter options for quality records
     */
    public function getFilterOptions(): JsonResponse
    {
        $filterOptions = [
            'date_filters' => DateRangeFilterService::getDateFilterOptions(),
            'date_columns' => DateRangeFilterService::getDateColumns('quality'),
            'status_options' => [
                'QA-Pending',
                'In Progress',
                'Completed',
                'Cancelled'
            ],
            'audit_status_options' => [
                'qualified',
                'unqualified'
            ]
        ];

        return $this->successResponse($filterOptions, 'Filter options retrieved successfully');
    }

    /**
     * Get single quality record
     */
    public function show(int $id): JsonResponse
    {
        $quality = Quality::with([
            'appointment',
            'appointment.business',
            'appointment.business.authPersons',
            'assignedUser',
            'answers.question',
        ])->find($id);

        if (!$quality) {
            return $this->errorResponse('Quality record not found', 404);
        }

        return $this->successResponse($quality, 'Quality record retrieved successfully');
    }

    /**
     * Get my quality assignments (for logged in QC user)
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $query = Quality::with([
            'appointment:id,date,followup_business_id',
            'appointment.business:id,name',
            'answers:id,quality_id,question_id,answers',
        ])->where('assigned_user', auth()->id());

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('auditstatus')) {
            $query->where('auditstatus', $request->auditstatus);
        }

        $qualities = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->successResponse($qualities, 'My quality assignments retrieved successfully');
    }

    /**
     * Update quality record status and audit status
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'auditstatus' => 'sometimes|in:qualified,unqualified',
            'status' => 'sometimes|string',
            'meeting_link' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $id) {
            $quality = Quality::find($id);
            if (!$quality) {
                return $this->errorResponse('Quality record not found', 404);
            }

            $updateData = [];
            if ($request->has('auditstatus')) {
                $updateData['auditstatus'] = $request->auditstatus;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('meeting_link')) {
                $updateData['meeting_link'] = $request->meeting_link;
            }

            $quality->update($updateData);

            return $this->successResponse($quality, 'Quality record updated successfully');
        }, 'Quality update');
    }

    /**
     * Reassign quality record to another QC user
     */
    public function reassign(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'assigned_user' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $result = $this->assignmentService->reassignQuality($id, $request->assigned_user);

        if (!$result) {
            return $this->errorResponse('Failed to reassign quality record', 400);
        }

        return $this->successResponse($result, 'Quality record reassigned successfully');
    }

    /**
     * Get workload statistics for Quality Control users
     */
    public function workloadStats(): JsonResponse
    {
        $stats = $this->assignmentService->getWorkloadStats();
        return $this->successResponse($stats, 'Workload statistics retrieved successfully');
    }

    /**
     * Submit quality answers for a quality record
     */
    public function submitAnswers(Request $request, int $qualityId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:quality_questions,id',
            'answers.*.answer' => 'required|in:yes,no,partially done,not applicable',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        return $this->executeTransaction(function () use ($request, $qualityId) {
            $quality = Quality::find($qualityId);
            if (!$quality) {
                return $this->errorResponse('Quality record not found', 404);
            }

            // Verify the quality record is assigned to current user
            if ($quality->assigned_user !== auth()->id()) {
                return $this->errorResponse('You are not assigned to this quality record', 403);
            }

            // Create or update answers
            foreach ($request->answers as $answerData) {
                QualityAnswer::updateOrCreate(
                    [
                        'quality_id' => $qualityId,
                        'question_id' => $answerData['question_id'],
                    ],
                    [
                        'answers' => $answerData['answer'],
                    ]
                );
            }

            // Update quality status if all questions answered
            $totalQuestions = QualityQuestion::count();
            $answeredCount = $quality->answers()->count();
            if ($answeredCount >= $totalQuestions) {
                $quality->update(['status' => 'Completed']);
            } else {
                $quality->update(['status' => 'In Progress']);
            }

            return $this->successResponse($quality->load('answers.question'), 'Quality answers submitted successfully');
        }, 'Quality answers submission');
    }
}
