<?php

namespace App\Http\Controllers\Api\Deals;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Comment;
use App\Models\Consultation;
use App\Models\Deal;
use App\Models\FollowupBusiness;
use App\Services\DateRangeFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class DealsController extends BaseApiController
{
    public const DEAL_STAGES = [
        'New Deal Created',
        'Proposal Sent',
        'Negotation',
        'Contact Sent',
        'Closed Won',
        'Closed Lost',
        'On Hold',
    ];

    private DateRangeFilterService $dateRangeFilterService;

    public function __construct(DateRangeFilterService $dateRangeFilterService)
    {
        $this->dateRangeFilterService = $dateRangeFilterService;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformDealItem(Deal $deal): array
    {
        $amountExcVat = (float) ($deal->amount_exc_vat ?? 0);
        $vat = (float) ($deal->vat ?? 0);

        $authPerson = $deal->authPerson;

        return [
            'id' => $deal->id,
            'name' => $deal->name,
            'selected_service' => $deal->selected_service,
            'type' => $deal->type,
            'source' => $deal->type,
            'deal_stage' => $deal->deal_stage,
            'lost_reason' => $deal->lost_reason,
            'probability' => $deal->probability,
            'estimated_closed_date' => $deal->estimated_closed_date,
            'amount_exc_vat' => $deal->amount_exc_vat,
            'vat' => $deal->vat,
            'value' => round($amountExcVat + $vat, 2),
            'next_activity' => $deal->next_activity,
            'priority' => $deal->priority,
            'followup_business_id' => $deal->followup_business_id,
            'auth_person_id' => $deal->auth_person_id,
            'created_by' => $deal->created_by,
            'created_at' => $deal->created_at,
            'updated_at' => $deal->updated_at,
            'company' => $deal->followupBusiness ? [
                'id' => $deal->followupBusiness->id,
                'name' => $deal->followupBusiness->name,
                'category' => $deal->followupBusiness->category,
                'type' => $deal->followupBusiness->type,
                'website' => $deal->followupBusiness->website,
            ] : null,
            'contact' => $authPerson ? [
                'id' => $authPerson->id,
                'title' => $authPerson->title,
                'firstname' => $authPerson->firstname,
                'middlename' => $authPerson->middlename,
                'lastname' => $authPerson->lastname,
                'name' => trim($authPerson->firstname.' '.$authPerson->lastname),
                'email' => $authPerson->primaryemail,
                'phone' => $authPerson->primarymobile ?? $authPerson->primaryphone,
                'job_title' => $authPerson->job_title,
            ] : null,
            'owner' => $deal->creator ? [
                'id' => $deal->creator->id,
                'first_name' => $deal->creator->first_name,
                'last_name' => $deal->creator->last_name,
                'email' => $deal->creator->email,
                'username' => $deal->creator->username,
            ] : null,
        ];
    }

    private function baseDealQuery()
    {
        return Deal::with([
            'followupBusiness',
            'authPerson',
            'creator:id,first_name,last_name,email,username',
        ]);
    }

    private function applySearchFilter($query, ?string $search): void
    {
        if (!$search) {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('deals.name', 'like', '%'.$search.'%')
                ->orWhere('deals.type', 'like', '%'.$search.'%')
                ->orWhere('deals.selected_service', 'like', '%'.$search.'%')
                ->orWhereHas('followupBusiness', function ($businessQuery) use ($search) {
                    $businessQuery->where('name', 'like', '%'.$search.'%');
                })
                ->orWhereHas('creator', function ($userQuery) use ($search) {
                    $userQuery->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%');
                });
        });
    }

    /**
     * @return array<string, mixed>|null Validation errors array, or null when valid.
     */
    private function validateDealStageFilter(Request $request): ?array
    {
        if (!$request->filled('deal_stage')) {
            return null;
        }

        $validator = Validator::make($request->all(), [
            'deal_stage' => ['required', Rule::in(self::DEAL_STAGES)],
        ]);

        return $validator->fails() ? $validator->errors()->toArray() : null;
    }

    /**
     * List deals with optional stage filter, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        if ($stageErrors = $this->validateDealStageFilter($request)) {
            return $this->errorResponse('Validation failed', 422, $stageErrors);
        }

        $query = $this->baseDealQuery();

        if ($request->filled('deal_stage')) {
            $query->where('deal_stage', $request->input('deal_stage'));
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }

        if ($request->filled('followup_business_id')) {
            $query->where('followup_business_id', $request->input('followup_business_id'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $this->applySearchFilter($query, $request->input('search'));

        $query = $this->dateRangeFilterService->applyFilters($query, $request, [
            'date_column' => $request->input('date_column', 'created_at'),
            'user_column' => 'created_by',
            'search_columns' => [],
        ]);

        $summaryQuery = clone $query;
        $stageDealCount = (clone $summaryQuery)->count();
        $stageTotalValue = (float) (clone $summaryQuery)
            ->selectRaw('COALESCE(SUM(COALESCE(amount_exc_vat, 0) + COALESCE(vat, 0)), 0) as total_value')
            ->value('total_value');

        $perPage = max(1, (int) $request->get('per_page', 15));
        $deals = $query->orderByDesc('deals.created_at')
            ->orderByDesc('deals.id')
            ->cursorPaginate($perPage)
            ->through(fn (Deal $deal) => $this->transformDealItem($deal));

        $paginatorArray = $deals->toArray();
        $paginatorArray['summary'] = [
            'deal_stage' => $request->input('deal_stage'),
            'deal_count' => $stageDealCount,
            'total_value' => round($stageTotalValue, 2),
        ];

        return $this->successResponse($paginatorArray, 'Deals retrieved successfully');
    }

    /**
     * Filter options for deals UI.
     */
    public function getFilterOptions(): JsonResponse
    {
        return $this->successResponse([
            'deal_stages' => self::DEAL_STAGES,
            'date_filters' => $this->dateRangeFilterService->getDateFilterOptions(),
            'date_columns' => [
                'created_at' => 'Created Date',
                'updated_at' => 'Updated Date',
                'estimated_closed_date' => 'Expected Close Date',
            ],
            'priority_options' => ['Low', 'Medium', 'High', 'Urgent'],
        ], 'Deal filter options retrieved successfully');
    }

    /**
     * Latest consultation per appointment (same rule as consultation listings).
     */
    private function applyLatestConsultationPerAppointment($query): void
    {
        $query->join(DB::raw('(SELECT appointment_id, MAX(created_at) as max_created_at
                           FROM consultations
                           GROUP BY appointment_id) as latest'), function ($join) {
            $join->on('consultations.appointment_id', '=', 'latest.appointment_id')
                ->on('consultations.created_at', '=', 'latest.max_created_at');
        });
    }

    /**
     * Business IDs whose latest consultation is conducted + Conducted Offered.
     */
    private function eligibleBusinessIdsQuery()
    {
        $query = Consultation::query()->select('appointments.followup_business_id');
        $this->applyLatestConsultationPerAppointment($query);

        return $query
            ->join('appointments', 'consultations.appointment_id', '=', 'appointments.id')
            ->whereRaw('LOWER(consultations.status) = ?', ['conducted'])
            ->whereRaw('LOWER(consultations.custom_status) = ?', ['conducted offered'])
            ->distinct();
    }

    private function isEligibleBusiness(int $followupBusinessId): bool
    {
        return $this->eligibleBusinessIdsQuery()
            ->where('appointments.followup_business_id', $followupBusinessId)
            ->exists();
    }

    /**
     * Businesses eligible for create-deal form (conducted + Conducted Offered consultation).
     */
    public function getFormBusinesses(Request $request): JsonResponse
    {
        $eligibleIds = $this->eligibleBusinessIdsQuery();

        $query = FollowupBusiness::query()
            ->whereIn('id', $eligibleIds)
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $businesses = $query->get(['id', 'name', 'category', 'type', 'website'])
            ->map(fn (FollowupBusiness $business) => [
                'id' => $business->id,
                'name' => $business->name,
                'category' => $business->category,
                'type' => $business->type,
                'website' => $business->website,
            ])
            ->values();

        return $this->successResponse($businesses, 'Eligible businesses retrieved successfully');
    }

    /**
     * Auth persons linked to a selected eligible business (create-deal form).
     */
    public function getFormBusinessAuthPersons(int $followupBusinessId): JsonResponse
    {
        if (!$this->isEligibleBusiness($followupBusinessId)) {
            return $this->errorResponse('Business is not eligible for deal creation', 404);
        }

        $business = FollowupBusiness::with('authPersons')->find($followupBusinessId);

        if (!$business) {
            return $this->errorResponse('Business not found', 404);
        }

        $persons = $business->authPersons->map(function ($person) {
            return [
                'id' => $person->id,
                'title' => $person->title,
                'firstname' => $person->firstname,
                'middlename' => $person->middlename,
                'lastname' => $person->lastname,
                'name' => trim($person->firstname.' '.$person->lastname),
                'email' => $person->primaryemail,
                'phone' => $person->primarymobile ?? $person->primaryphone,
                'job_title' => $person->job_title,
            ] + $person->profileFieldsForResponse();
        })->values();

        return $this->successResponse($persons, 'Business contacts retrieved successfully');
    }

    /**
     * Create a new deal and optionally add business comments.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'followup_business_id' => 'required|exists:followup_businesses,id',
            'auth_person_id' => 'nullable|exists:followup_auth_persons,id',
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:100',
            'deal_stage' => ['nullable', Rule::in(self::DEAL_STAGES)],
            'lost_reason' => 'nullable|string',
            'probability' => 'nullable|numeric|min:0|max:100',
            'estimated_closed_date' => 'nullable|date',
            'selected_service' => 'nullable|string|max:255',
            'amount_exc_vat' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'next_activity' => 'nullable|string|max:255',
            'priority' => 'nullable|string|max:50',
            'comments' => 'nullable|array',
            'comments.*.comment' => 'sometimes|required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $deal = DB::transaction(function () use ($request) {
            $deal = Deal::create([
                'followup_business_id' => $request->followup_business_id,
                'auth_person_id' => $request->auth_person_id,
                'name' => $request->name,
                'type' => $request->type,
                'deal_stage' => $request->deal_stage ?? 'New Deal Created',
                'lost_reason' => $request->lost_reason,
                'probability' => $request->probability,
                'estimated_closed_date' => $request->estimated_closed_date,
                'selected_service' => $request->selected_service,
                'amount_exc_vat' => $request->amount_exc_vat,
                'vat' => $request->vat,
                'next_activity' => $request->next_activity,
                'priority' => $request->priority,
                'created_by' => auth()->id(),
            ]);

            if ($request->has('comments') && is_array($request->comments)) {
                foreach ($request->comments as $commentData) {
                    Comment::create([
                        'followup_business_id' => $request->followup_business_id,
                        'comment' => $commentData['comment'] ?? null,
                        'old_status' => $commentData['old_status'] ?? null,
                        'new_status' => $commentData['new_status'] ?? $deal->deal_stage,
                        'created_by' => auth()->id(),
                    ]);
                }
            }

            return $deal;
        });

        $deal->load(['followupBusiness', 'authPerson', 'creator']);

        return $this->successResponse(
            $this->transformDealItem($deal),
            'Deal created successfully',
            201
        );
    }

    /**
     * Get a single deal by ID.
     */
    public function show(string $id): JsonResponse
    {
        $deal = $this->baseDealQuery()
            ->with(['followupBusiness.comments' => fn ($q) => $q->latest()])
            ->find($id);

        if (!$deal) {
            return $this->errorResponse('Deal not found', 404);
        }

        $payload = $this->transformDealItem($deal);
        $payload['comments'] = $deal->followupBusiness?->comments?->map(function ($comment) {
            return [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'old_status' => $comment->old_status,
                'new_status' => $comment->new_status,
                'created_by' => $comment->created_by,
                'created_at' => $comment->created_at,
            ];
        })->values()->all() ?? [];

        return $this->successResponse($payload, 'Deal retrieved successfully');
    }

    /**
     * Update a deal and optionally append business comments.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $deal = Deal::find($id);

        if (!$deal) {
            return $this->errorResponse('Deal not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'followup_business_id' => 'sometimes|required|exists:followup_businesses,id',
            'auth_person_id' => 'sometimes|nullable|exists:followup_auth_persons,id',
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|nullable|string|max:100',
            'deal_stage' => ['sometimes', 'nullable', Rule::in(self::DEAL_STAGES)],
            'lost_reason' => 'sometimes|nullable|string',
            'probability' => 'sometimes|nullable|numeric|min:0|max:100',
            'estimated_closed_date' => 'sometimes|nullable|date',
            'selected_service' => 'sometimes|nullable|string|max:255',
            'amount_exc_vat' => 'sometimes|nullable|numeric|min:0',
            'vat' => 'sometimes|nullable|numeric|min:0',
            'next_activity' => 'sometimes|nullable|string|max:255',
            'priority' => 'sometimes|nullable|string|max:50',
            'comments' => 'nullable|array',
            'comments.*.comment' => 'sometimes|required|string',
            'comments.*.old_status' => 'nullable|string|max:255',
            'comments.*.new_status' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        DB::transaction(function () use ($request, $deal) {
            $deal->update($request->only([
                'followup_business_id',
                'auth_person_id',
                'name',
                'type',
                'deal_stage',
                'lost_reason',
                'probability',
                'estimated_closed_date',
                'selected_service',
                'amount_exc_vat',
                'vat',
                'next_activity',
                'priority',
            ]));

            $businessId = $deal->followup_business_id;

            if ($request->has('comments') && is_array($request->comments) && $businessId) {
                foreach ($request->comments as $commentData) {
                    Comment::create([
                        'followup_business_id' => $businessId,
                        'comment' => $commentData['comment'] ?? null,
                        'old_status' => $commentData['old_status'] ?? null,
                        'new_status' => $commentData['new_status'] ?? $deal->deal_stage,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
        });

        $deal->refresh()->load(['followupBusiness', 'authPerson', 'creator']);

        return $this->successResponse(
            $this->transformDealItem($deal),
            'Deal updated successfully'
        );
    }

    /**
     * Delete a deal.
     */
    public function destroy(string $id): JsonResponse
    {
        $deal = Deal::find($id);

        if (!$deal) {
            return $this->errorResponse('Deal not found', 404);
        }

        $deal->delete();

        return $this->successResponse(null, 'Deal deleted successfully');
    }
}
