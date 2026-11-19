<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Api\BaseApiController;
use App\Models\BillingSetup;
use App\Models\BillingSetupItem;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BillingSetupsController extends BaseApiController
{
    /**
     * @return array<string, mixed>
     */
    private function transformSetup(BillingSetup $setup): array
    {
        return [
            'id' => $setup->id,
            'contract_id' => $setup->contract_id,
            'proposal_id' => $setup->proposal_id,
            'followup_business_id' => $setup->followup_business_id,
            'invoice_frequency' => $setup->invoice_frequency,
            'billing_day' => $setup->billing_day,
            'billing_start_date' => $setup->billing_start_date?->toDateString(),
            'due_days' => $setup->due_days,
            'buffer_days' => $setup->buffer_days,
            'next_invoice_date' => $setup->next_invoice_date?->toDateString(),
            'status' => $setup->status,
            'notes' => $setup->notes,
            'created_by' => $setup->created_by,
            'created_at' => $setup->created_at,
            'updated_at' => $setup->updated_at,
            'items' => $setup->items->map(fn (BillingSetupItem $item) => [
                'id' => $item->id,
                'service_id' => $item->service_id,
                'billing_type' => $item->billing_type,
                'amount' => $item->amount,
                'vat_amount' => $item->vat_amount,
                'service_duration' => $item->service_duration,
                'duration_unit' => $item->duration_unit,
                'start_date' => $item->start_date?->toDateString(),
                'end_date' => $item->end_date?->toDateString(),
                'next_bill_date' => $item->next_bill_date?->toDateString(),
                'status' => $item->status,
                'service' => $item->service ? [
                    'id' => $item->service->id,
                    'name' => $item->service->name,
                    'status' => $item->service->status,
                ] : null,
            ])->values(),
            'contract' => $setup->contract ? [
                'id' => $setup->contract->id,
                'document_id' => $setup->contract->document_id,
                'status' => $setup->contract->status,
            ] : null,
            'company' => $setup->followupBusiness ? [
                'id' => $setup->followupBusiness->id,
                'name' => $setup->followupBusiness->name,
                'category' => $setup->followupBusiness->category,
                'type' => $setup->followupBusiness->type,
                'website' => $setup->followupBusiness->website,
            ] : null,
            'owner' => $setup->creator ? [
                'id' => $setup->creator->id,
                'first_name' => $setup->creator->first_name,
                'last_name' => $setup->creator->last_name,
                'email' => $setup->creator->email,
                'username' => $setup->creator->username,
            ] : null,
        ];
    }

    private function baseQuery()
    {
        return BillingSetup::with([
            'items.service',
            'contract',
            'followupBusiness',
            'creator:id,first_name,last_name,email,username',
        ]);
    }

    /**
     * Signed contracts available for a new billing setup (one setup per contract).
     */
    public function getFormContracts(Request $request): JsonResponse
    {
        $query = Contract::with('followupBusiness:id,name')
            ->where('status', Contract::STATUS_SIGNED)
            ->whereDoesntHave('billingSetup')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('followupBusiness', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
        }

        $contracts = $query->get()->map(fn (Contract $contract) => [
            'id' => $contract->id,
            'document_id' => $contract->document_id,
            'proposal_id' => $contract->proposal_id,
            'followup_business_id' => $contract->followup_business_id,
            'business_name' => $contract->followupBusiness?->name,
        ])->values();

        return $this->successResponse($contracts, 'Signed contracts retrieved successfully');
    }

    /**
     * Contract context for the billing setup form — proposal services to bill.
     */
    public function getFormContractContext(int $contractId): JsonResponse
    {
        $contract = Contract::with(['followupBusiness', 'proposal.services.service'])->find($contractId);

        if (!$contract) {
            return $this->errorResponse('Contract not found', 404);
        }

        $services = $contract->proposal
            ? $contract->proposal->services->map(fn ($item) => [
                'service_id' => $item->service_id,
                'service_name' => $item->service?->name,
                'amount' => $item->amount,
                'vat_amount' => $item->vat_amount,
                'estimation_duration' => $item->estimation_duration,
                'estimation_unit' => $item->estimation_unit,
            ])->values()
            : collect();

        return $this->successResponse([
            'contract' => [
                'id' => $contract->id,
                'document_id' => $contract->document_id,
                'status' => $contract->status,
                'proposal_id' => $contract->proposal_id,
                'followup_business_id' => $contract->followup_business_id,
            ],
            'business' => $contract->followupBusiness ? [
                'id' => $contract->followupBusiness->id,
                'name' => $contract->followupBusiness->name,
            ] : null,
            'services' => $services,
        ], 'Contract billing context retrieved successfully');
    }

    /**
     * List billing setups with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->baseQuery();

        foreach (['contract_id', 'proposal_id', 'followup_business_id', 'status', 'invoice_frequency', 'created_by'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        $perPage = max(1, (int) $request->get('per_page', 15));
        $setups = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage)
            ->through(fn (BillingSetup $setup) => $this->transformSetup($setup));

        return $this->successResponse($setups->toArray(), 'Billing setups retrieved successfully');
    }

    /**
     * Create a billing setup for a signed contract.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'invoice_frequency' => 'required|in:weekly,monthly,quarterly,yearly',
            'billing_day' => 'nullable|integer|min:1|max:31',
            'billing_start_date' => 'required|date',
            'due_days' => 'nullable|integer|min:0|max:365',
            'buffer_days' => 'nullable|integer|min:0|max:365',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.billing_type' => 'required|in:one_off,recurring',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.vat_amount' => 'required|numeric|min:0',
            'items.*.service_duration' => 'nullable|numeric|min:0',
            'items.*.duration_unit' => 'nullable|string|max:50',
            'items.*.start_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $contract = Contract::find($request->contract_id);

        if ($contract->status !== Contract::STATUS_SIGNED) {
            return $this->errorResponse('Validation failed', 422, [
                'contract_id' => ['Billing can only be set up for signed contracts.'],
            ]);
        }

        if (BillingSetup::where('contract_id', $contract->id)->exists()) {
            return $this->errorResponse('Validation failed', 422, [
                'contract_id' => ['A billing setup already exists for this contract.'],
            ]);
        }

        $setup = DB::transaction(function () use ($request, $contract) {
            $setup = BillingSetup::create([
                'contract_id' => $contract->id,
                'proposal_id' => $contract->proposal_id,
                'followup_business_id' => $contract->followup_business_id,
                'invoice_frequency' => $request->invoice_frequency,
                'billing_day' => $request->billing_day,
                'billing_start_date' => $request->billing_start_date,
                'due_days' => $request->input('due_days', 7),
                'buffer_days' => $request->input('buffer_days', 0),
                'next_invoice_date' => $request->billing_start_date,
                'status' => BillingSetup::STATUS_ACTIVE,
                'notes' => $request->notes,
                'created_by' => auth()->id(),
            ]);

            $this->createItems($setup, $request->items);

            return $setup;
        });

        $setup->load(['items.service', 'contract', 'followupBusiness', 'creator:id,first_name,last_name,email,username']);

        return $this->successResponse(
            $this->transformSetup($setup),
            'Billing setup created successfully',
            201
        );
    }

    /**
     * Get a single billing setup.
     */
    public function show(int $id): JsonResponse
    {
        $setup = $this->baseQuery()->find($id);

        if (!$setup) {
            return $this->errorResponse('Billing setup not found', 404);
        }

        return $this->successResponse(
            $this->transformSetup($setup),
            'Billing setup retrieved successfully'
        );
    }

    /**
     * Update a billing setup (config fields and/or full item replacement).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $setup = BillingSetup::find($id);

        if (!$setup) {
            return $this->errorResponse('Billing setup not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'invoice_frequency' => 'sometimes|required|in:weekly,monthly,quarterly,yearly',
            'billing_day' => 'nullable|integer|min:1|max:31',
            'billing_start_date' => 'sometimes|required|date',
            'due_days' => 'nullable|integer|min:0|max:365',
            'buffer_days' => 'nullable|integer|min:0|max:365',
            'next_invoice_date' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:Active,Paused,Completed',
            'notes' => 'nullable|string',
            'items' => 'sometimes|required|array|min:1',
            'items.*.service_id' => 'required|exists:services,id',
            'items.*.billing_type' => 'required|in:one_off,recurring',
            'items.*.amount' => 'required|numeric|min:0',
            'items.*.vat_amount' => 'required|numeric|min:0',
            'items.*.service_duration' => 'nullable|numeric|min:0',
            'items.*.duration_unit' => 'nullable|string|max:50',
            'items.*.start_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        DB::transaction(function () use ($request, $setup) {
            $setup->update($request->only([
                'invoice_frequency',
                'billing_day',
                'billing_start_date',
                'due_days',
                'buffer_days',
                'next_invoice_date',
                'status',
                'notes',
            ]));

            if ($request->has('items')) {
                $setup->items()->delete();
                $this->createItems($setup, $request->items);
            }
        });

        $setup->refresh()->load(['items.service', 'contract', 'followupBusiness', 'creator:id,first_name,last_name,email,username']);

        return $this->successResponse(
            $this->transformSetup($setup),
            'Billing setup updated successfully'
        );
    }

    /**
     * Delete a billing setup (invoices already generated are kept).
     */
    public function destroy(int $id): JsonResponse
    {
        $setup = BillingSetup::find($id);

        if (!$setup) {
            return $this->errorResponse('Billing setup not found', 404);
        }

        if ($setup->invoices()->exists()) {
            $setup->update(['status' => BillingSetup::STATUS_COMPLETED]);

            return $this->successResponse(null, 'Billing setup has generated invoices — marked Completed instead of deleted');
        }

        $setup->delete();

        return $this->successResponse(null, 'Billing setup deleted successfully');
    }

    /**
     * Create setup items with computed end_date and initial next_bill_date.
     */
    private function createItems(BillingSetup $setup, array $items): void
    {
        foreach ($items as $item) {
            $startDate = $item['start_date'];
            $endDate = BillingSetupItem::computeEndDate(
                $startDate,
                $item['service_duration'] ?? null,
                $item['duration_unit'] ?? null
            );

            $setup->items()->create([
                'service_id' => (int) $item['service_id'],
                'billing_type' => $item['billing_type'],
                'amount' => (float) $item['amount'],
                'vat_amount' => (float) $item['vat_amount'],
                'service_duration' => $item['service_duration'] ?? null,
                'duration_unit' => $item['duration_unit'] ?? null,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'next_bill_date' => $startDate,
                'status' => $startDate <= now()->toDateString()
                    ? BillingSetupItem::STATUS_ACTIVE
                    : BillingSetupItem::STATUS_PENDING,
            ]);
        }
    }
}
