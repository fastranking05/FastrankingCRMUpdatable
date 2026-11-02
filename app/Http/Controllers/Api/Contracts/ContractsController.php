<?php

namespace App\Http\Controllers\Api\Contracts;

use App\Http\Controllers\Api\BaseApiController;
use App\Mail\ContractMail;
use App\Models\Contract;
use App\Models\Email;
use App\Models\FollowupBusiness;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ContractsController extends BaseApiController
{
    private function transformContractItem(Contract $contract): array
    {
        return [
            'id' => $contract->id,
            'document_id' => $contract->document_id,
            'proposal_id' => $contract->proposal_id,
            'followup_business_id' => $contract->followup_business_id,
            'document' => $contract->document,
            'created_by' => $contract->created_by,
            'created_at' => $contract->created_at,
            'updated_at' => $contract->updated_at,
            'proposal' => $contract->proposal ? [
                'id' => $contract->proposal->id,
                'email' => $contract->proposal->email,
            ] : null,
            'company' => $contract->followupBusiness ? [
                'id' => $contract->followupBusiness->id,
                'name' => $contract->followupBusiness->name,
                'category' => $contract->followupBusiness->category,
                'type' => $contract->followupBusiness->type,
                'website' => $contract->followupBusiness->website,
            ] : null,
            'owner' => $contract->creator ? [
                'id' => $contract->creator->id,
                'first_name' => $contract->creator->first_name,
                'last_name' => $contract->creator->last_name,
                'email' => $contract->creator->email,
                'username' => $contract->creator->username,
            ] : null,
        ];
    }

    private function findContract(string $id): ?Contract
    {
        return Contract::with(['proposal', 'followupBusiness', 'creator:id,first_name,last_name,email,username'])
            ->where('id', $id)
            ->orWhere('document_id', $id)
            ->first();
    }

    /**
     * List contracts with optional filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Contract::with(['proposal', 'followupBusiness', 'creator:id,first_name,last_name,email,username']);

        if ($request->filled('proposal_id')) {
            $query->where('proposal_id', $request->input('proposal_id'));
        }

        if ($request->filled('followup_business_id')) {
            $query->where('followup_business_id', $request->input('followup_business_id'));
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->input('created_by'));
        }

        $perPage = max(1, (int) $request->get('per_page', 15));
        $contracts = $query->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage)
            ->through(fn (Contract $contract) => $this->transformContractItem($contract));

        return $this->successResponse($contracts->toArray(), 'Contracts retrieved successfully');
    }

    /**
     * Create a contract document.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'proposal_id' => 'required|exists:proposals,id',
            'followup_business_id' => 'required|exists:followup_businesses,id',
            'document' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $contract = Contract::create([
            'proposal_id' => $request->proposal_id,
            'followup_business_id' => $request->followup_business_id,
            'document' => $request->document,
            'created_by' => auth()->id(),
        ]);

        $contract->load(['proposal', 'followupBusiness', 'creator:id,first_name,last_name,email,username']);

        return $this->successResponse(
            $this->transformContractItem($contract),
            'Contract created successfully',
            201
        );
    }

    /**
     * Get a single contract by ID or document_id (UUID).
     */
    public function show(string $id): JsonResponse
    {
        $contract = $this->findContract($id);

        if (!$contract) {
            return $this->errorResponse('Contract not found', 404);
        }

        return $this->successResponse(
            $this->transformContractItem($contract),
            'Contract retrieved successfully'
        );
    }

    /**
     * Update a contract document.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $contract = $this->findContract($id);

        if (!$contract) {
            return $this->errorResponse('Contract not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'proposal_id' => 'sometimes|required|exists:proposals,id',
            'followup_business_id' => 'sometimes|required|exists:followup_businesses,id',
            'document' => 'sometimes|required|array',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $contract->update($request->only([
            'proposal_id',
            'followup_business_id',
            'document',
        ]));

        $contract->refresh()->load(['proposal', 'followupBusiness', 'creator:id,first_name,last_name,email,username']);

        return $this->successResponse(
            $this->transformContractItem($contract),
            'Contract updated successfully'
        );
    }

    /**
     * Delete a contract.
     */
    public function destroy(string $id): JsonResponse
    {
        $contract = $this->findContract($id);

        if (!$contract) {
            return $this->errorResponse('Contract not found', 404);
        }

        $contract->delete();

        return $this->successResponse(null, 'Contract deleted successfully');
    }

    /**
     * Public endpoint — get a contract by document_id (no auth; used by the
     * e-sign page to render the document).
     */
    public function publicShow(string $documentId): JsonResponse
    {
        $contract = $this->findContract($documentId);

        if (!$contract) {
            return $this->errorResponse('Contract not found', 404);
        }

        return $this->successResponse(
            $this->transformContractItem($contract),
            'Contract retrieved successfully'
        );
    }

    /**
     * Public endpoint — submit the client signature for a contract (no auth).
     */
    public function publicSign(Request $request, string $documentId): JsonResponse
    {
        $contract = $this->findContract($documentId);

        if (!$contract) {
            return $this->errorResponse('Contract not found', 404);
        }

        $document = $contract->document ?? [];

        if (!empty($document['client_signed_at']) || !empty($document['signatures']['client'])) {
            return $this->errorResponse('Contract already signed', 409, [
                'client_signed_at' => $document['client_signed_at'] ?? null,
            ]);
        }

        $validator = Validator::make($request->all(), [
            'signature' => 'required|string',
            'signed_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $document['signatures']['client'] = $request->signature;
        $document['client_signed_at'] = $request->input('signed_at', now()->toIso8601String());

        $contract->update(['document' => $document]);
        $contract->refresh()->load(['proposal', 'followupBusiness', 'creator:id,first_name,last_name,email,username']);

        return $this->successResponse(
            $this->transformContractItem($contract),
            'Contract signed successfully'
        );
    }

    /**
     * Email a contract document (frontend-generated PDF) to a recipient.
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pdf' => 'sometimes|file|mimes:pdf|max:10240',
            'sign_url' => 'sometimes|nullable|url|max:2048',
            'email' => 'required|email|max:255',
            'business_id' => 'required|exists:followup_businesses,id',
            'auth_person_id' => 'nullable|exists:followup_auth_persons,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
            'cc' => 'nullable|array',
            'cc.*' => 'nullable|email',
            'bcc' => 'nullable|array',
            'bcc.*' => 'nullable|email',
            'filename' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        if (!$request->hasFile('pdf') && !$request->filled('sign_url')) {
            return $this->errorResponse('Validation failed', 422, [
                'pdf' => ['A PDF file or sign_url is required.'],
            ]);
        }

        $business = FollowupBusiness::with('authPersons')->find($request->business_id);

        $contact = $request->filled('auth_person_id')
            ? $business?->authPersons->firstWhere('id', (int) $request->auth_person_id)
            : null;

        $contactName = $contact
            ? trim($contact->firstname.' '.$contact->lastname)
            : '';

        $companyName = Setting::orderByDesc('id')->value('company_name') ?: (string) config('app.name');
        $subject = $request->input('subject')
            ?: 'Contract - '.($business?->name ?? $companyName);
        $message = trim($request->input('message')
            ?: 'Please find attached the contract document for '.($business?->name ?? 'your business').'.');

        $hasGreeting = (bool) preg_match('/^\s*(dear|hi|hello|namaste)\b/i', $message);
        $hasSignOff = (bool) preg_match('/\b(regards|sincerely|thank(s|ing)?\s+you)\b/i', $message);

        $pdfPath = $request->hasFile('pdf') ? $request->file('pdf')->getRealPath() : null;
        $pdfName = $request->filled('filename')
            ? (str_ends_with($request->filename, '.pdf') ? $request->filename : $request->filename.'.pdf')
            : 'Contract.pdf';
        $signUrl = $request->input('sign_url');

        try {
            Mail::to($request->email)
                ->cc($request->input('cc', []))
                ->bcc($request->input('bcc', []))
                ->send(new ContractMail(
                    $subject,
                    $message,
                    $pdfPath,
                    $pdfName,
                    $companyName,
                    $contactName !== '' ? $contactName : 'there',
                    $business?->name ?? '',
                    ! $hasGreeting,
                    ! $hasSignOff,
                    $signUrl,
                ));

            $email = Email::create([
                'followup_business_id' => $request->business_id,
                'to' => [$request->email],
                'cc' => $request->input('cc'),
                'bcc' => $request->input('bcc'),
                'type' => 'contract',
                'created_by' => auth()->id(),
            ]);

            return $this->successResponse([
                'email_id' => $email->id,
                'sent_to' => $request->email,
            ], 'Contract sent successfully');
        } catch (\Throwable $e) {
            Log::error('Failed to send contract', [
                'business_id' => $request->business_id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to send contract: '.$e->getMessage(), 500, [
                'error' => config('app.debug') ? $e->getMessage() : null,
            ]);
        }
    }
}
