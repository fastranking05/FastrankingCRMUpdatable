<?php

namespace App\Http\Controllers\Api\Proposals;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\Consultation;
use App\Models\FollowupBusiness;
use App\Models\Proposal;
use App\Models\ProposalService;
use App\Models\Service;
use App\Services\DateRangeFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $services = $proposal->services->map(fn (ProposalService $item) => [
            'id' => $item->id,
            'service_id' => $item->service_id,
            'amount' => $item->amount,
            'vat_amount' => $item->vat_amount,
            'total_value' => round((float) $item->amount + (float) $item->vat_amount, 2),
            'service' => $item->service ? [
                'id' => $item->service->id,
                'name' => $item->service->name,
                'status' => $item->service->status,
            ] : null,
        ])->values();

        $totalAmount = (float) $proposal->services->sum(fn ($item) => (float) $item->amount);
        $totalVat = (float) $proposal->services->sum(fn ($item) => (float) $item->vat_amount);
        $authPerson = $proposal->authPerson;

        return [
            'id' => $proposal->id,
            'business_id' => $proposal->business_id,
            'auth_person_id' => $proposal->auth_person_id,
            'email' => $proposal->email,
            'services' => $services,
            'total_amount' => round($totalAmount, 2),
            'total_vat' => round($totalVat, 2),
            'total_value' => round($totalAmount + $totalVat, 2),
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
            'services.service',
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
                ->orWhereHas('services.service', function ($serviceQuery) use ($search) {
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
        int $authPersonId
    ): ?array {
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

        if ($request->filled('service_id')) {
            $query->whereHas('services', fn ($q) => $q->where('service_id', $request->input('service_id')));
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
        $totals = (clone $summaryQuery)
            ->join('proposal_services', 'proposals.id', '=', 'proposal_services.proposal_id')
            ->selectRaw('COALESCE(SUM(proposal_services.amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(proposal_services.vat_amount), 0) as total_vat')
            ->first();
        $totalAmount = (float) ($totals->total_amount ?? 0);
        $totalVat = (float) ($totals->total_vat ?? 0);

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
     * Businesses with a conducted consultation, for the proposal creation form.
     */
    public function getFormBusinesses(Request $request): JsonResponse
    {
        $conductedBusinessIds = Consultation::query()
            ->select('appointments.followup_business_id')
            ->join('appointments', 'consultations.appointment_id', '=', 'appointments.id')
            ->whereRaw('LOWER(consultations.status) = ?', ['conducted'])
            ->distinct();

        $query = FollowupBusiness::query()
            ->whereIn('id', $conductedBusinessIds)
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('id', 'like', '%'.$search.'%');
            });
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

        return $this->successResponse($businesses, 'Businesses for proposal form retrieved successfully');
    }

    /**
     * Selected business context (contacts) for proposal form prefill.
     */
    public function getFormBusinessContext(int $businessId): JsonResponse
    {
        $business = FollowupBusiness::with('authPersons')->find($businessId);

        if (!$business) {
            return $this->errorResponse('Business not found', 404);
        }

        $contacts = $business->authPersons->map(function ($person) {
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

        return $this->successResponse([
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'category' => $business->category,
                'type' => $business->type,
                'website' => $business->website,
            ],
            'contacts' => $contacts,
            'suggested_email' => $contacts->first()['email'] ?? null,
        ], 'Business context retrieved successfully');
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
            'email' => 'required|email|max:255',
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.amount' => 'required|numeric|min:0',
            'services.*.vat_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        if ($relationErrors = $this->validateProposalRelations(
            (int) $request->business_id,
            (int) $request->auth_person_id
        )) {
            return $this->errorResponse('Validation failed', 422, $relationErrors);
        }

        $proposal = DB::transaction(function () use ($request) {
            $proposal = Proposal::create([
                'business_id' => $request->business_id,
                'auth_person_id' => $request->auth_person_id,
                'email' => $request->email,
                'created_by' => (string) auth()->id(),
            ]);

            foreach ($request->services as $item) {
                $proposal->services()->create([
                    'service_id' => (int) $item['service_id'],
                    'amount' => (float) $item['amount'],
                    'vat_amount' => (float) $item['vat_amount'],
                ]);
            }

            return $proposal;
        });

        $proposal->load(['followupBusiness', 'authPerson', 'services.service', 'creator']);

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
            'email' => 'sometimes|required|email|max:255',
            'services' => 'sometimes|required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.amount' => 'required|numeric|min:0',
            'services.*.vat_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        if ($request->hasAny(['business_id', 'auth_person_id'])) {
            $businessId = (int) ($request->input('business_id', $proposal->business_id));
            $authPersonId = (int) ($request->input('auth_person_id', $proposal->auth_person_id));

            if ($relationErrors = $this->validateProposalRelations($businessId, $authPersonId)) {
                return $this->errorResponse('Validation failed', 422, $relationErrors);
            }
        }

        DB::transaction(function () use ($request, $proposal) {
            $proposal->update($request->only([
                'business_id',
                'auth_person_id',
                'email',
            ]));

            if ($request->has('services')) {
                $proposal->services()->delete();

                foreach ($request->services as $item) {
                    $proposal->services()->create([
                        'service_id' => (int) $item['service_id'],
                        'amount' => (float) $item['amount'],
                        'vat_amount' => (float) $item['vat_amount'],
                    ]);
                }
            }
        });

        $proposal->refresh()->load(['followupBusiness', 'authPerson', 'services.service', 'creator']);

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
