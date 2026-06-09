<?php

namespace App\Http\Controllers\Api\Quality;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Quality;
use App\Models\QualityAnswer;
use App\Models\QualityQuestion;
use App\Services\QualityAssignmentService;
use App\Services\DateRangeFilterService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * Display a listing of quality records.
     */
    public function index(Request $request): JsonResponse
    {
        // 1. Start with the latest quality record per appointment
        $latestQualityIds = Quality::selectRaw('MAX(id) as id')
            ->groupBy('appointment_id')
            ->pluck('id');

        $query = Quality::with([
            'appointment',
            'appointment.followupBusiness.authPersons',
            'appointment.timeSlot',
            'assignedUser',
            'answers',
        ])->whereIn('id', $latestQualityIds);

        // 2. Build filters array from request
        $filters = [];

        // Status filter (your payload sends "status": "QA-Approved")
        if ($request->has('status')) {
            $filters['status'] = $request->input('status');
        }

        // Audit status filter
        if ($request->has('auditstatus')) {
            $filters['auditstatus'] = $request->input('auditstatus');
        }

        // Score filters
        if ($request->has('score_min')) {
            $filters['score_min'] = $request->input('score_min');
        }
        if ($request->has('score_max')) {
            $filters['score_max'] = $request->input('score_max');
        }
        if ($request->has('score')) {
            $filters['score'] = $request->input('score');
        }

        // Appointment date filter from the "appointments" payload structure
        if ($request->has('appointments') && is_array($request->input('appointments'))) {
            $appointmentFilter = $request->input('appointments')[0] ?? [];
            if (isset($appointmentFilter['date'])) {
                $filters['appointment_date_filter'] = $appointmentFilter['date'];
                $filters['custom_start_date'] = $appointmentFilter['custom_start_date'] ?? null;
                $filters['custom_end_date']   = $appointmentFilter['custom_end_date'] ?? null;
            }
        }

        // 3. Apply all filters using the model scope
        $query->filter($filters);

        // 4. Order and paginate (cursor-safe column names)
        $perPage = max(1, (int) $request->input('per_page', 15));
        $qualities = $query->orderByDesc('qualities.created_at')
            ->orderByDesc('qualities.id')
            ->cursorPaginate($perPage)
            ->through(fn (Quality $quality) => $this->transformQualityIndexItem($quality));

        return $this->successResponse($qualities, 'All quality data retrieved successfully');
    }

    /**
     * Get appointment IDs matching date filter
     */
    private function getAppointmentDateFilterIds($appointments): ?array
    {
        $dateFilter = null;
        $customStartDate = null;
        $customEndDate = null;

        // Extract date filter from appointments array
        if (is_array($appointments) && count($appointments) > 0) {
            $firstAppointment = $appointments[0];
            if (is_array($firstAppointment) && isset($firstAppointment['date'])) {
                $dateFilter = $firstAppointment['date'];
            }
            if (is_array($firstAppointment) && isset($firstAppointment['custom_start_date'])) {
                $customStartDate = $firstAppointment['custom_start_date'];
            }
            if (is_array($firstAppointment) && isset($firstAppointment['custom_end_date'])) {
                $customEndDate = $firstAppointment['custom_end_date'];
            }
        }

        if (!$dateFilter && !$customStartDate) {
            return null;
        }

        // Build date condition for appointments table
        $dateCondition = '';
        $bindings = [];

        switch ($dateFilter) {
            case 'today':
                $todayDate = Carbon::today()->toDateString();
                $dateCondition = 'DATE(date) = ?';
                $bindings[] = $todayDate;
                break;

            case 'yesterday':
                $yesterdayDate = Carbon::yesterday()->toDateString();
                $dateCondition = 'DATE(date) = ?';
                $bindings[] = $yesterdayDate;
                break;

            case 'this_week':
                $dateCondition = 'DATE(date) BETWEEN ? AND ?';
                $bindings[] = Carbon::now()->startOfWeek()->toDateString();
                $bindings[] = Carbon::now()->endOfWeek()->toDateString();
                break;

            case 'last_week':
                $dateCondition = 'DATE(date) BETWEEN ? AND ?';
                $bindings[] = Carbon::now()->subWeek()->startOfWeek()->toDateString();
                $bindings[] = Carbon::now()->subWeek()->endOfWeek()->toDateString();
                break;

            case 'this_month':
                $dateCondition = 'MONTH(date) = ? AND YEAR(date) = ?';
                $bindings[] = Carbon::now()->month;
                $bindings[] = Carbon::now()->year;
                break;

            case 'last_month':
                $dateCondition = 'MONTH(date) = ? AND YEAR(date) = ?';
                $bindings[] = Carbon::now()->subMonth()->month;
                $bindings[] = Carbon::now()->subMonth()->year;
                break;

            case 'this_year':
                $dateCondition = 'YEAR(date) = ?';
                $bindings[] = Carbon::now()->year;
                break;

            case 'last_year':
                $dateCondition = 'YEAR(date) = ?';
                $bindings[] = Carbon::now()->subYear()->year;
                break;

            case 'custom':
                if ($customStartDate && $customEndDate) {
                    $dateCondition = 'DATE(date) BETWEEN ? AND ?';
                    $bindings[] = Carbon::parse($customStartDate)->toDateString();
                    $bindings[] = Carbon::parse($customEndDate)->toDateString();
                } elseif ($customStartDate) {
                    $dateCondition = 'DATE(date) >= ?';
                    $bindings[] = Carbon::parse($customStartDate)->toDateString();
                } elseif ($customEndDate) {
                    $dateCondition = 'DATE(date) <= ?';
                    $bindings[] = Carbon::parse($customEndDate)->toDateString();
                }
                break;
        }

        if ($dateCondition) {
            // Use raw SQL to get appointment IDs matching the date filter
            $appointmentIds = DB::select("SELECT id FROM appointments WHERE $dateCondition", $bindings);
            return array_column($appointmentIds, 'id');
        }

        return null;
    }

    /**
     * Apply appointment-based filters
     */
    private function applyAppointmentFilters($query, Request $request): void
    {
        $appointmentColumns = $request->input('appointments', []);

        foreach ($appointmentColumns as $column) {
            switch ($column) {
                case 'date':
                    $this->applyAppointmentDateFilter($query, $request);
                    break;
                    // Add more appointment columns as needed
                    // case 'followup_business_id':
                    //     if ($request->has('followup_business_id')) {
                    //         $query->whereHas('appointment', function ($q) use ($request) {
                    //             $q->where('followup_business_id', $request->input('followup_business_id'));
                    //         });
                    //     }
                    //     break;
            }
        }
    }

    /**
     * Apply appointment date filter using relationship
     */
    private function applyAppointmentDateFilter($query, Request $request): void
    {
        $dateFilter = $request->input('date_filter');
        $customStartDate = $request->input('custom_start_date');
        $customEndDate = $request->input('custom_end_date');

        Log::info('Applying appointment date filter', [
            'date_filter' => $dateFilter,
            'custom_start_date' => $customStartDate,
            'custom_end_date' => $customEndDate,
            'today' => Carbon::today()->toDateString()
        ]);

        if (!$dateFilter && !$customStartDate) {
            return;
        }

        // Build date condition for appointments table
        $dateCondition = '';
        $bindings = [];

        switch ($dateFilter) {
            case 'today':
                $todayStart = Carbon::today()->startOfDay();
                $todayEnd = Carbon::today()->endOfDay();
                Log::info('Filtering for today', ['start' => $todayStart, 'end' => $todayEnd]);
                $dateCondition = 'date BETWEEN ? AND ?';
                $bindings[] = $todayStart;
                $bindings[] = $todayEnd;
                break;

            case 'yesterday':
                $yesterdayStart = Carbon::yesterday()->startOfDay();
                $yesterdayEnd = Carbon::yesterday()->endOfDay();
                $dateCondition = 'date BETWEEN ? AND ?';
                $bindings[] = $yesterdayStart;
                $bindings[] = $yesterdayEnd;
                break;

            case 'this_week':
                $dateCondition = 'date BETWEEN ? AND ?';
                $bindings[] = Carbon::now()->startOfWeek();
                $bindings[] = Carbon::now()->endOfWeek();
                break;

            case 'last_week':
                $dateCondition = 'date BETWEEN ? AND ?';
                $bindings[] = Carbon::now()->subWeek()->startOfWeek();
                $bindings[] = Carbon::now()->subWeek()->endOfWeek();
                break;

            case 'this_month':
                $dateCondition = 'MONTH(date) = ? AND YEAR(date) = ?';
                $bindings[] = Carbon::now()->month;
                $bindings[] = Carbon::now()->year;
                break;

            case 'last_month':
                $dateCondition = 'MONTH(date) = ? AND YEAR(date) = ?';
                $bindings[] = Carbon::now()->subMonth()->month;
                $bindings[] = Carbon::now()->subMonth()->year;
                break;

            case 'this_year':
                $dateCondition = 'YEAR(date) = ?';
                $bindings[] = Carbon::now()->year;
                break;

            case 'last_year':
                $dateCondition = 'YEAR(date) = ?';
                $bindings[] = Carbon::now()->subYear()->year;
                break;

            case 'custom':
                if ($customStartDate && $customEndDate) {
                    $dateCondition = 'date BETWEEN ? AND ?';
                    $bindings[] = Carbon::parse($customStartDate)->startOfDay();
                    $bindings[] = Carbon::parse($customEndDate)->endOfDay();
                } elseif ($customStartDate) {
                    $dateCondition = 'DATE(date) >= ?';
                    $bindings[] = Carbon::parse($customStartDate);
                } elseif ($customEndDate) {
                    $dateCondition = 'DATE(date) <= ?';
                    $bindings[] = Carbon::parse($customEndDate);
                }
                break;
        }

        if ($dateCondition) {
            // Check all appointments to see what dates exist first
            $allAppointments = DB::select("SELECT id, date FROM appointments LIMIT 10");
            Log::info('Sample appointments from database', ['appointments' => $allAppointments]);

            // Use raw SQL to get appointment IDs matching the date filter
            $appointmentIds = DB::select("SELECT id, date FROM appointments WHERE $dateCondition", $bindings);
            $appointmentIdArray = array_column($appointmentIds, 'id');

            Log::info('Appointment IDs matching date filter', [
                'condition' => $dateCondition,
                'bindings' => $bindings,
                'count' => count($appointmentIdArray),
                'ids' => $appointmentIdArray,
                'appointments' => $appointmentIds
            ]);

            // Filter quality records by these appointment IDs
            if (count($appointmentIdArray) > 0) {
                $query->whereIn('appointment_id', $appointmentIdArray);
            } else {
                Log::warning('No appointments found matching date filter', ['condition' => $dateCondition]);
                // Return empty result by adding impossible condition
                $query->where('id', '=', 0);
            }
        }
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
            'appointment.followupBusiness',
            'appointment.followupBusiness.authPersons',
            'appointment.timeSlot',
            'appointment.creator:id,first_name,middle_name,last_name,username',
            'assignedUser',
            'answers.question',
        ])->find($id);

        if (!$quality) {
            return $this->errorResponse('Quality record not found', 404);
        }

        // Transform the response to change 'creator' to 'appointment_creator'
        $transformedQuality = $this->transformQualityResponse($quality);

        return $this->successResponse($transformedQuality, 'Quality record retrieved successfully');
    }

    /**
     * Transform quality response to change creator to appointment_creator
     */
    private function transformQualityResponse($quality): object
    {
        $data = $quality->toArray();

        if (isset($data['appointment']['creator'])) {
            $data['appointment']['appointment_creator'] = $data['appointment']['creator'];
            unset($data['appointment']['creator']);
        }

        return (object) $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformQualityIndexItem(Quality $quality): array
    {
        return [
            'id' => $quality->id,
            'appointment_id' => $quality->appointment_id,
            'auditstatus' => $quality->auditstatus,
            'status' => $quality->status,
            'score' => $quality->score,
            'assigned_user' => [
                'id' => $quality->assignedUser->id ?? null,
                'first_name' => $quality->assignedUser->first_name ?? null,
                'last_name' => $quality->assignedUser->last_name ?? null,
                'email' => $quality->assignedUser->email ?? null,
            ],
            'meeting_link' => $quality->meeting_link,
            'created_at' => $quality->created_at,
            'updated_at' => $quality->updated_at,
            'answers' => $quality->answers->map(fn ($answer) => [
                'id' => $answer->id,
                'question_id' => $answer->question_id,
                'answer' => $answer->answers,
                'score' => $answer->score,
            ])->toArray(),
            'business' => $quality->appointment?->followupBusiness ? [
                'id' => $quality->appointment->followupBusiness->id,
                'name' => $quality->appointment->followupBusiness->name,
                'category' => $quality->appointment->followupBusiness->category,
                'type' => $quality->appointment->followupBusiness->type,
                'website' => $quality->appointment->followupBusiness->website,
                'phone' => $quality->appointment->followupBusiness->phone,
                'email' => $quality->appointment->followupBusiness->email,
                'auth_persons' => $quality->appointment->followupBusiness->authPersons->map(fn ($person) => [
                    'id' => $person->id,
                    'title' => $person->title,
                    'firstname' => $person->firstname,
                    'middlename' => $person->middlename,
                    'lastname' => $person->lastname,
                    'designation' => $person->designation,
                    'primaryemail' => $person->primaryemail,
                    'primarymobile' => $person->primarymobile,
                    'is_primary' => $person->pivot->is_primary ?? 0,
                ])->toArray(),
            ] : null,
            'appointment_date' => $quality->appointment?->date,
            'appointment_source' => $quality->appointment?->source,
            'appointment_current_status' => $quality->appointment?->current_status,
            'appointment_slot' => ($quality->appointment && $quality->appointment->timeSlot) ? [
                'id' => $quality->appointment->timeSlot->id,
                'start_time' => $quality->appointment->timeSlot->start_time,
                'end_time' => $quality->appointment->timeSlot->end_time,
            ] : null,
        ];
    }

    /**
     * Get my quality assignments (for logged in QC user)
     */
    public function myAssignments(Request $request): JsonResponse
    {
        $query = Quality::with([
            'appointment:id,date,followup_business_id',
            'appointment.followupBusiness:id,name',
            'answers:id,quality_id,question_id,answers',
        ])->where('assigned_user', auth()->id());

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('auditstatus')) {
            $query->where('auditstatus', $request->auditstatus);
        }

        $qualities = $query->orderByDesc('qualities.created_at')
            ->orderByDesc('qualities.id')
            ->cursorPaginate($request->get('per_page', 15));

        return $this->successResponse($qualities, 'My quality assignments retrieved successfully');
    }

    /**
     * Update quality record status and audit status
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'auditstatus' => 'sometimes|in:qualified,unqualified,pending',
            'status' => 'sometimes|string',
            'meeting_link' => 'nullable|url',
            'score' => 'nullable|numeric|min:0|max:100',
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
            if ($request->has('score')) {
                $updateData['score'] = $request->score;
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
