<?php

namespace App\Http\Controllers\Api\Billing;

use App\Http\Controllers\Api\BaseApiController;
use App\Mail\InvoiceMail;
use App\Models\Email;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\Setting;
use App\Services\DateRangeFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class InvoicesController extends BaseApiController
{
    private DateRangeFilterService $dateRangeFilterService;

    public function __construct(DateRangeFilterService $dateRangeFilterService)
    {
        $this->dateRangeFilterService = $dateRangeFilterService;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformInvoice(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'billing_setup_id' => $invoice->billing_setup_id,
            'contract_id' => $invoice->contract_id,
            'proposal_id' => $invoice->proposal_id,
            'followup_business_id' => $invoice->followup_business_id,
            'invoice_date' => $invoice->invoice_date?->toDateString(),
            'due_date' => $invoice->due_date?->toDateString(),
            'buffer_until' => $invoice->buffer_until?->toDateString(),
            'period_start' => $invoice->period_start?->toDateString(),
            'period_end' => $invoice->period_end?->toDateString(),
            'subtotal' => $invoice->subtotal,
            'vat_amount' => $invoice->vat_amount,
            'total_amount' => $invoice->total_amount,
            'previous_balance' => $invoice->previous_balance,
            'grand_total' => $invoice->grand_total,
            'amount_paid' => $invoice->amount_paid,
            'balance' => $invoice->balance,
            'status' => $invoice->status,
            'sent_at' => $invoice->sent_at,
            'paid_at' => $invoice->paid_at,
            'notes' => $invoice->notes,
            'created_by' => $invoice->created_by,
            'created_at' => $invoice->created_at,
            'updated_at' => $invoice->updated_at,
            'items' => $invoice->items->map(fn (InvoiceItem $item) => [
                'id' => $item->id,
                'service_id' => $item->service_id,
                'description' => $item->description,
                'billing_type' => $item->billing_type,
                'period_start' => $item->period_start?->toDateString(),
                'period_end' => $item->period_end?->toDateString(),
                'amount' => $item->amount,
                'vat_amount' => $item->vat_amount,
                'total' => $item->total,
                'service' => $item->service ? [
                    'id' => $item->service->id,
                    'name' => $item->service->name,
                ] : null,
            ])->values(),
            'payments' => $invoice->payments->map(fn (InvoicePayment $payment) => [
                'id' => $payment->id,
                'amount' => $payment->amount,
                'payment_date' => $payment->payment_date?->toDateString(),
                'method' => $payment->method,
                'reference' => $payment->reference,
                'notes' => $payment->notes,
                'created_by' => $payment->created_by,
                'created_at' => $payment->created_at,
            ])->values(),
            'contract' => $invoice->contract ? [
                'id' => $invoice->contract->id,
                'document_id' => $invoice->contract->document_id,
                'status' => $invoice->contract->status,
            ] : null,
            'company' => $invoice->followupBusiness ? [
                'id' => $invoice->followupBusiness->id,
                'name' => $invoice->followupBusiness->name,
                'category' => $invoice->followupBusiness->category,
                'type' => $invoice->followupBusiness->type,
                'website' => $invoice->followupBusiness->website,
            ] : null,
            'owner' => $invoice->creator ? [
                'id' => $invoice->creator->id,
                'first_name' => $invoice->creator->first_name,
                'last_name' => $invoice->creator->last_name,
                'email' => $invoice->creator->email,
                'username' => $invoice->creator->username,
            ] : null,
        ];
    }

    private function baseQuery()
    {
        return Invoice::with([
            'items.service',
            'payments',
            'contract',
            'followupBusiness',
            'creator:id,first_name,last_name,email,username',
        ]);
    }

    /**
     * List invoices with filters, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = $this->baseQuery();

        foreach (['billing_setup_id', 'contract_id', 'proposal_id', 'followup_business_id', 'status', 'created_by'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', '%'.$search.'%')
                    ->orWhereHas('followupBusiness', fn ($bq) => $bq->where('name', 'like', '%'.$search.'%'));
            });
        }

        $query = $this->dateRangeFilterService->applyFilters($query, $request, [
            'date_column' => $request->input('date_column', 'invoice_date'),
            'user_column' => 'created_by',
            'search_columns' => [],
        ]);

        $summaryQuery = clone $query;
        $summary = [
            'invoice_count' => (clone $summaryQuery)->count(),
            'total_amount' => round((float) (clone $summaryQuery)->sum('total_amount'), 2),
            'amount_paid' => round((float) (clone $summaryQuery)->sum('amount_paid'), 2),
            'balance' => round((float) (clone $summaryQuery)->sum('balance'), 2),
        ];

        $perPage = max(1, (int) $request->get('per_page', 15));
        $invoices = $query->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->cursorPaginate($perPage)
            ->through(fn (Invoice $invoice) => $this->transformInvoice($invoice));

        $paginatorArray = $invoices->toArray();
        $paginatorArray['summary'] = $summary;

        return $this->successResponse($paginatorArray, 'Invoices retrieved successfully');
    }

    /**
     * Filter options for the invoices UI.
     */
    public function filterOptions(): JsonResponse
    {
        return $this->successResponse([
            'statuses' => [
                Invoice::STATUS_PENDING,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PARTIAL,
                Invoice::STATUS_PAID,
                Invoice::STATUS_OVERDUE,
                Invoice::STATUS_CANCELLED,
            ],
        ], 'Invoice filter options retrieved successfully');
    }

    /**
     * Get a single invoice with items and payments.
     */
    public function show(string $id): JsonResponse
    {
        $invoice = $this->baseQuery()->find($id);

        if (!$invoice) {
            return $this->errorResponse('Invoice not found', 404);
        }

        return $this->successResponse(
            $this->transformInvoice($invoice),
            'Invoice retrieved successfully'
        );
    }

    /**
     * Record a payment against an invoice.
     */
    public function recordPayment(Request $request, string $id): JsonResponse
    {
        $invoice = Invoice::with('payments')->find($id);

        if (!$invoice) {
            return $this->errorResponse('Invoice not found', 404);
        }

        if ($invoice->status === Invoice::STATUS_CANCELLED) {
            return $this->errorResponse('Cannot record a payment for a cancelled invoice', 422);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'nullable|date',
            'method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $invoice->payments()->create([
            'amount' => (float) $request->amount,
            'payment_date' => $request->input('payment_date', now()->toDateString()),
            'method' => $request->method,
            'reference' => $request->reference,
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        $invoice->recalculatePayments();
        $invoice->refresh()->load(['items.service', 'payments', 'contract', 'followupBusiness', 'creator:id,first_name,last_name,email,username']);

        return $this->successResponse(
            $this->transformInvoice($invoice),
            'Payment recorded successfully'
        );
    }

    /**
     * Cancel an invoice (not allowed once payments exist).
     */
    public function cancel(string $id): JsonResponse
    {
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return $this->errorResponse('Invoice not found', 404);
        }

        if ((float) $invoice->amount_paid > 0) {
            return $this->errorResponse('Cannot cancel an invoice with recorded payments', 422);
        }

        $invoice->update(['status' => Invoice::STATUS_CANCELLED, 'balance' => 0]);

        return $this->successResponse(
            $this->transformInvoice($invoice->refresh()->load(['items.service', 'payments', 'contract', 'followupBusiness', 'creator:id,first_name,last_name,email,username'])),
            'Invoice cancelled successfully'
        );
    }

    /**
     * Email the invoice (frontend-generated PDF) to a recipient.
     */
    public function send(Request $request, string $id): JsonResponse
    {
        $invoice = Invoice::with(['followupBusiness.authPersons'])->find($id);

        if (!$invoice) {
            return $this->errorResponse('Invoice not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'pdf' => 'required|file|mimes:pdf|max:10240',
            'email' => 'sometimes|nullable|email|max:255',
            'auth_person_id' => 'nullable|exists:followup_auth_persons,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed', 422, $validator->errors());
        }

        $contact = $request->filled('auth_person_id')
            ? $invoice->followupBusiness?->authPersons->firstWhere('id', (int) $request->auth_person_id)
            : $invoice->followupBusiness?->authPersons->first();

        $recipient = $request->input('email') ?: $contact?->primaryemail;

        if (!$recipient) {
            return $this->errorResponse('No recipient email — provide email or select a contact', 422);
        }

        $contactName = $contact ? trim($contact->firstname.' '.$contact->lastname) : '';
        $companyName = Setting::orderByDesc('id')->value('company_name') ?: (string) config('app.name');
        $pdfPath = $request->file('pdf')->getRealPath();

        try {
            Mail::to($recipient)->send(new InvoiceMail(
                $invoice,
                $pdfPath,
                'Invoice-'.$invoice->id.'.pdf',
                $companyName,
                $contactName !== '' ? $contactName : 'there',
                $invoice->followupBusiness?->name ?? '',
                (float) $invoice->grand_total,
                $invoice->due_date?->format('d M Y'),
            ));

            Email::create([
                'followup_business_id' => $invoice->followup_business_id,
                'to' => [$recipient],
                'type' => 'invoice',
                'created_by' => auth()->id(),
            ]);

            if (in_array($invoice->status, [Invoice::STATUS_PENDING, Invoice::STATUS_OVERDUE])) {
                $invoice->update(['status' => Invoice::STATUS_SENT, 'sent_at' => now()]);
            } else {
                $invoice->update(['sent_at' => now()]);
            }

            return $this->successResponse(
                $this->transformInvoice($invoice->refresh()->load(['items.service', 'payments', 'contract', 'followupBusiness', 'creator:id,first_name,last_name,email,username'])) + ['sent_to' => $recipient],
                'Invoice sent successfully'
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send invoice', [
                'invoice_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to send invoice: '.$e->getMessage(), 500, [
                'error' => config('app.debug') ? $e->getMessage() : null,
            ]);
        }
    }
}
