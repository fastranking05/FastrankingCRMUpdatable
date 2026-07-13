<?php

namespace App\Http\Controllers\Api\Proposals;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Deal;
use App\Models\FollowupBusiness;
use App\Models\Proposal;
use App\Models\Service;
use App\Services\DateRangeFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProposalsController extends BaseApiController
{
    private DateRangeFilterService $dateRangeFilterService;

    public function __construct(DateRangeFilterService $dateRangeFilterService)
    {
        $this->dateRangeFilterService = $dateRangeFilterService;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformProposalItem(Proposal $proposal): array
    {
        $amount = (float) ($proposal->amount ?? 0);
        $vatAmount = (float) ($proposal->vat_amount ?? 0);
        $authPerson = $proposal->authPerson;
        $deal = $proposal->deal;

        return [
            'id' => $proposal->id,
            'business_id' => $proposal->business_id,
            'auth_person_id' => $proposal->auth_person_id,
            'deal_id' => $proposal->deal_id,
            'email' => $proposal->email,
            'service_id' => $proposal->service_id,
            'amount' => $proposal->amount,
            'vat_amount' => $proposal->vat_amount,
            'total_value' => round($amount + $vatAmount, 2),
            'created_by' => $proposal->created_by,
            'created_at' => $proposal->created_at,
            'updated_at' => $proposal->updated_at,
            'company' => $proposal->followupBusiness ? [
                'id' => $proposal->followupBusiness->id,
                'name' => $proposal->followupBusiness->name,
                'category' => $proposal->followupBusiness->category,
                'type' => $proposal->followupBusiness->type,
                'website' => $proposal->followupBusiness->website,
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
            'deal' => $deal ? [
                'id' => $deal->id,
                'name' => $deal->name,
                'deal_stage' => $deal->deal_stage,
                'selected_service' => $deal->selected_service,
                'amount_exc_vat' => $deal->amount_exc_vat,
                'vat' => $deal->vat,
            ] : null,
            'service' => $proposal->service ? [
                'id' => $proposal->service->id,
                'name' => $proposal->service->name,
                'status' => $proposal->service->status,
            ] : null,
            'owner' => $proposal->creator ? [
                'id' => $proposal->creator->id,
                'first_name' => $proposal->creator->first_name,
                'last_name' => $proposal->creator->last_name,
                'email' => $proposal->creator->email,
                'username' => $proposal->creator->username,
            ] : null,
        ];
    }

    private function baseProposalQuery()
    {
        return Proposal::with([
            'followupBusiness',
            'authPerson',
            'deal',
            'service',
            'creator:id,first_name,last_name,email,username',
        ]);
    }

    private function applySearchFilter($query, ?string $search): void
    {
        if (!$search) {
            return;
        }

        $query->where(function ($q) use ($search) {
            $q->where('proposals.email', 'like', '%'.$search.'%')
                ->orWhere('proposals.id', 'like', '%'.$search.'%')
                ->orWhereHas('followupBusiness', function ($businessQuery) use ($search) {
                    $businessQuery->where('name', 'like', '%'.$search.'%');
                })
                ->orWhereHas('deal', function ($dealQuery) use ($search) {
                    $dealQuery->where('name', 'like', '%'.$search.'%');
                })
                ->orWhereHas('service', function ($serviceQuery) use ($search) {
                    $serviceQuery->where('name', 'like', '%'.$search.'%');
                })
                ->orWhereHas('creator', function ($userQuery) use ($search) {
                    $userQuery->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('username', 'like', '%'.$search.'%');
                });
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validateProposalRelations(
        int $businessId,
        int $authPersonId,
        string $dealId
    ): ?array {
        $deal = Deal::find($dealId);

        if (!$deal) {
            return ['deal_id' => ['The selected deal does not exist.']];
        }

        if ((int) $deal->followup_business_id !== $businessId) {
            return ['business_id' => ['The selected business does not match the deal.']];
        }

        if ($deal->auth_person_id !== null && (int) $deal->auth_person_id !== $authPersonId) {
            return ['auth_person_id' => ['The selected contact does not match the deal.']];
        }

        $businessHasContact = FollowupBusiness::query()
            ->where('id', $businessId)
            ->whereHas('authPersons', fn ($q) => $q->where('followup_auth_persons.id', $authPersonId))
            ->exists();

        if (!$businessHasContact) {
            return ['auth_person_id' => ['The selected contact is not linked to the business.']];
        }

        return null;
    }

    /**
     * List proposals with optional filters, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->baseProposalQuery();

        if ($request->filled('business_id')) {
            $query->where('business_id', $request->input('business_id'));
        }

        if ($request->filled('auth_person_id')) {
            $query->where('auth_person_id', $request->input('auth_person_id'));
        }

        if ($request->filled('deal_id')) {
            $query->where('deal_id', $request->input('deal_id'));
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->input('service_id'));
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }

        $this->applySearchFilter($query, $request->input('search'));

        $query = $this->dateRangeFilterService->applyFilters($query, $request, [
            'date_column' => $request->input('date_column', 'created_at'),
            'user_column' => 'created_by',
            'search_columns' => [],
        ]);

        $summaryQuery = clone $query;
        $proposalCount = (clone $summaryQuery)->count();
        $totalAmount = (float) (clone $summaryQuery)
            ->selectRaw('COALESCE(SUM(CAST(amount AS DECIMAL(15,2))), 0) as total_amount')
            ->value('total_amount');
        $totalVat = (float) (clone $summaryQuery)
            ->selectRaw('COALESCE(SUM(CAST(vat_amount AS DECIMAL(15,2))), 0) as total_vat')
            ->value('total_vat');

        $perPage = max(1, (int) $request->get('per_page', 15));
        $proposals = $query->orderByDesc('proposals.created_at')
            ->orderByDesc('proposals.id')
            ->cursorPaginate($perPage)
            ->through(fn (Proposal $proposal) => $this->transformProposalItem($proposal));

        $paginatorArray = $proposals->toArray();
        $paginatorArray['summary'] = [
            'proposal_count' => $proposalCount,
            'total_amount' => round($totalAmount, 2),
            'total_vat' => round($totalVat, 2),
            'total_value' => round($totalAmount + $totalVat, 2),
        ];

        return $this->successResponse($paginatorArray, 'Proposals retrieved successfully');
    }

    /**
     * Filter options for proposals UI.
     */
    public function getFilterOptions(): JsonResponse
    {
        return $this->successResponse([
            'date_filters' => $this->dateRangeFilterService->getDateFilterOptions(),
            'date_columns' => [
                'created_at' => 'Created Date',
                'updated_at' => 'Updated Date',
            ],
        ], 'Proposal filter options retrieved successfully');
    }

    /**
     * Deals available for proposal creation form.
     */
    public function getFormDeals(Request $request): JsonResponse
    {
        $query = Deal::with(['followupBusiness:id,name', 'authPerson:id,firstname,lastname,primaryemail'])
            ->orderByDesc('created_at');

        if ($request->filled('business_id')) {
            $query->where('followup_business_id', $request->input('business_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('id', 'like', '%'.$search.'%')
                    ->orWhereHas('followupBusiness', fn ($bq) => $bq->where('name', 'like', '%'.$search.'%'));
            });
        }

        $deals = $query->get(['id', 'name', 'deal_stage', 'followup_business_id', 'auth_person_id', 'selected_service'])
            ->map(fn (Deal $deal) => [
                'id' => $deal->id,
                'name' => $deal->name,
                'deal_stage' => $deal->deal_stage,
                'selected_service' => $deal->selected_service,
                'followup_business_id' => $deal->followup_business_id,
                'auth_person_id' => $deal->auth_person_id,
                'company_name' => $deal->followupBusiness?->name,
                'contact_name' => $deal->authPerson
                    ? trim($deal->authPerson->firstname.' '.$deal->authPerson->lastname)
                    : null,
                'contact_email' => $deal->authPerson?->primaryemail,
            ])
            ->values();

        return $this->successResponse($deals, 'Deals for proposal form retrieved successfully');
    }

    /**
     * Selected deal context for proposal form prefill.
     */
    public function getFormDealContext(string $dealId): JsonResponse
    {
        $deal = Deal::with(['followupBusiness', 'authPerson'])->find($dealId);

        if (!$deal) {
            return $this->errorResponse('Deal not found', 404);
        }

        $authPerson = $deal->authPerson;

        return $this->successResponse([
            'deal' => [
                'id' => $deal->id,
                'name' => $deal->name,
                'deal_stage' => $deal->deal_stage,
                'selected_service' => $deal->selected_service,
                'amount_exc_vat' => $deal->amount_exc_vat,
                'vat' => $deal->vat,
            ],
            'business_id' => $deal->followup_business_id,
            'auth_person_id' => $deal->auth_person_id,
            'suggested_email' => $authPerson?->primaryemail,
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
            ] + $authPerson->profileFieldsForResponse() : null,
        ], 'Deal context retrieved successfully');
    }

    /**
     * Active services for proposal form.
     */
    public function getFormServices(Request $request): JsonResponse
    {
        $query = Service::query()
            ->where('status', true)
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        $services = $query->get(['id', 'name', 'status'])
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'name' => $service->name,
                'status' => $service->status,
            ])
            ->values();

        return $this->successResponse($services, 'Services retrieved successfully');
    }

    /**
     * Create a new proposal.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|exists:followup_businesses,id',
            'auth_person_id' => 'required|exists:followup_auth_persons,id',
            'deal_id' => 'required|exists:deals,id',
            'email' => 'required|email|max:255',
            'service_id' => 'required|exists:services,id',
            'amount' => 'required|numeric|min:0',
            'vat_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        if ($relationErrors = $this->validateProposalRelations(
            (int) $request->business_id,
            (int) $request->auth_person_id,
            (string) $request->deal_id
        )) {
            return $this->errorResponse('Validation failed', 422, $relationErrors);
        }

        $proposal = Proposal::create([
            'business_id' => $request->business_id,
            'auth_person_id' => $request->auth_person_id,
            'deal_id' => $request->deal_id,
            'email' => $request->email,
            'service_id' => (string) $request->service_id,
            'amount' => (string) $request->amount,
            'vat_amount' => (string) $request->vat_amount,
            'created_by' => (string) auth()->id(),
        ]);

        $proposal->load(['followupBusiness', 'authPerson', 'deal', 'service', 'creator']);

        return $this->successResponse(
            $this->transformProposalItem($proposal),
            'Proposal created successfully',
            201
        );
    }

    /**
     * Get a single proposal by ID.
     */
    public function show(string $id): JsonResponse
    {
        $proposal = $this->baseProposalQuery()->find($id);

        if (!$proposal) {
            return $this->errorResponse('Proposal not found', 404);
        }

        return $this->successResponse(
            $this->transformProposalItem($proposal),
            'Proposal retrieved successfully'
        );
    }

    /**
     * Update a proposal.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $proposal = Proposal::find($id);

        if (!$proposal) {
            return $this->errorResponse('Proposal not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'business_id' => 'sometimes|required|exists:followup_businesses,id',
            'auth_person_id' => 'sometimes|required|exists:followup_auth_persons,id',
            'deal_id' => 'sometimes|required|exists:deals,id',
            'email' => 'sometimes|required|email|max:255',
            'service_id' => 'sometimes|required|exists:services,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'vat_amount' => 'sometimes|required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $businessId = (int) ($request->input('business_id', $proposal->business_id));
        $authPersonId = (int) ($request->input('auth_person_id', $proposal->auth_person_id));
        $dealId = (string) ($request->input('deal_id', $proposal->deal_id));

        if ($request->hasAny(['business_id', 'auth_person_id', 'deal_id'])) {
            if ($relationErrors = $this->validateProposalRelations($businessId, $authPersonId, $dealId)) {
                return $this->errorResponse('Validation failed', 422, $relationErrors);
            }
        }

        $updateData = $request->only([
            'business_id',
            'auth_person_id',
            'deal_id',
            'email',
            'amount',
            'vat_amount',
        ]);

        if ($request->has('service_id')) {
            $updateData['service_id'] = (string) $request->service_id;
        }

        if ($request->has('amount')) {
            $updateData['amount'] = (string) $request->amount;
        }

        if ($request->has('vat_amount')) {
            $updateData['vat_amount'] = (string) $request->vat_amount;
        }

        $proposal->update($updateData);
        $proposal->refresh()->load(['followupBusiness', 'authPerson', 'deal', 'service', 'creator']);

        return $this->successResponse(
            $this->transformProposalItem($proposal),
            'Proposal updated successfully'
        );
    }

    /**
     * Delete a proposal.
     */
    public function destroy(string $id): JsonResponse
    {
        $proposal = Proposal::find($id);

        if (!$proposal) {
            return $this->errorResponse('Proposal not found', 404);
        }

        $proposal->delete();

        return $this->successResponse(null, 'Proposal deleted successfully');
    }
}
