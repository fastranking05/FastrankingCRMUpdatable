<?php

namespace App\Services\Billing;

use App\Models\BillingSetup;
use App\Models\BillingSetupItem;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceGenerationService
{
    /**
     * Generate the invoice scheduled at $setup->next_invoice_date.
     *
     * Bills every item whose next_bill_date has arrived and is within its
     * service window (end_date). Carries the unpaid balance of prior invoices
     * into previous_balance. Advances each item's next_bill_date and the
     * setup's next_invoice_date. Returns null when nothing was due.
     */
    public function generateForSetup(BillingSetup $setup, ?Carbon $asOf = null): ?Invoice
    {
        $asOf = $asOf ?? Carbon::today();
        $invoiceDate = $setup->next_invoice_date?->copy() ?? $asOf;

        // Items whose service window has ended are finished billing — a
        // "2 months" service starting Nov 1 bills Nov + Dec (end Jan 1).
        $setup->items()
            ->where('status', '!=', BillingSetupItem::STATUS_COMPLETED)
            ->whereNotNull('end_date')
            ->where(function ($q) {
                $q->whereNull('next_bill_date')
                    ->orWhereColumn('next_bill_date', '>=', 'end_date');
            })
            ->update([
                'status' => BillingSetupItem::STATUS_COMPLETED,
                'next_bill_date' => null,
            ]);

        $dueItems = $setup->items()
            ->where('status', '!=', BillingSetupItem::STATUS_COMPLETED)
            ->whereNotNull('next_bill_date')
            ->whereDate('next_bill_date', '<=', $invoiceDate)
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhereColumn('next_bill_date', '<', 'end_date');
            })
            ->get();

        $nextSetupDate = $this->advance($invoiceDate, $setup->invoice_frequency);

        if ($dueItems->isEmpty()) {
            $this->advanceSetup($setup, $nextSetupDate);

            return null;
        }

        return DB::transaction(function () use ($setup, $dueItems, $invoiceDate, $nextSetupDate, $asOf) {
            $subtotal = round((float) $dueItems->sum(fn ($i) => (float) $i->amount), 2);
            $vat = round((float) $dueItems->sum(fn ($i) => (float) $i->vat_amount), 2);
            $total = round($subtotal + $vat, 2);

            $previousBalance = round((float) $setup->invoices()
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->sum('balance'), 2);

            $grandTotal = round($total + $previousBalance, 2);

            $dueDate = $invoiceDate->copy()->addDays((int) $setup->due_days);
            $bufferUntil = $dueDate->copy()->addDays((int) $setup->buffer_days);
            $periodEnd = $nextSetupDate->copy()->subDay();

            $invoice = Invoice::create([
                'billing_setup_id' => $setup->id,
                'contract_id' => $setup->contract_id,
                'proposal_id' => $setup->proposal_id,
                'followup_business_id' => $setup->followup_business_id,
                'invoice_date' => $invoiceDate->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'buffer_until' => $bufferUntil->toDateString(),
                'period_start' => $invoiceDate->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'subtotal' => $subtotal,
                'vat_amount' => $vat,
                'total_amount' => $total,
                'previous_balance' => $previousBalance,
                'grand_total' => $grandTotal,
                'amount_paid' => 0,
                'balance' => $grandTotal,
                'status' => Invoice::STATUS_PENDING,
                'created_by' => $setup->created_by,
            ]);

            foreach ($dueItems as $item) {
                $invoice->items()->create([
                    'service_id' => $item->service_id,
                    'billing_setup_item_id' => $item->id,
                    'description' => $item->service?->name,
                    'billing_type' => $item->billing_type,
                    'period_start' => $item->next_bill_date?->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                    'amount' => $item->amount,
                    'vat_amount' => $item->vat_amount,
                    'total' => round((float) $item->amount + (float) $item->vat_amount, 2),
                ]);

                $this->advanceItem($item, $setup->invoice_frequency);
            }

            $this->advanceSetup($setup, $nextSetupDate);

            Log::info('Invoice generated', [
                'invoice_id' => $invoice->id,
                'billing_setup_id' => $setup->id,
                'invoice_date' => $invoiceDate->toDateString(),
                'items_billed' => $dueItems->count(),
                'total' => $total,
                'previous_balance' => $previousBalance,
                'grand_total' => $grandTotal,
            ]);

            return $invoice;
        });
    }

    /**
     * Generate invoices for every active setup whose next_invoice_date has
     * arrived (catches up multiple missed cycles). Returns invoices created.
     */
    public function generateDue(?Carbon $asOf = null): array
    {
        $asOf = $asOf ?? Carbon::today();
        $generated = [];

        $setupIds = BillingSetup::where('status', BillingSetup::STATUS_ACTIVE)
            ->whereDate('next_invoice_date', '<=', $asOf)
            ->pluck('id');

        foreach ($setupIds as $setupId) {
            $setup = BillingSetup::with('items.service')->find($setupId);

            $safety = 24; // cap catch-up cycles per run
            while ($safety-- > 0
                && $setup->status === BillingSetup::STATUS_ACTIVE
                && $setup->next_invoice_date
                && $setup->next_invoice_date->lte($asOf)) {
                $invoice = $this->generateForSetup($setup, $asOf);
                if ($invoice) {
                    $generated[] = $invoice->id;
                }
                $setup->refresh();
            }
        }

        return $generated;
    }

    /**
     * Mark invoices Overdue once the due date + buffer days have passed
     * with a remaining balance.
     */
    public function markOverdue(?Carbon $asOf = null): int
    {
        $asOf = $asOf ?? Carbon::today();

        return Invoice::whereIn('status', [
            Invoice::STATUS_PENDING,
            Invoice::STATUS_SENT,
            Invoice::STATUS_PARTIAL,
        ])
            ->where('balance', '>', 0)
            ->whereDate('buffer_until', '<', $asOf)
            ->update(['status' => Invoice::STATUS_OVERDUE]);
    }

    /**
     * Advance a billed item to its next bill date or completion.
     */
    private function advanceItem(BillingSetupItem $item, string $frequency): void
    {
        if ($item->billing_type === BillingSetupItem::TYPE_ONE_OFF) {
            $item->update(['status' => BillingSetupItem::STATUS_COMPLETED, 'next_bill_date' => null]);

            return;
        }

        $next = $this->advance($item->next_bill_date ?? Carbon::today(), $frequency);

        if ($item->end_date && $next->gte($item->end_date)) {
            $item->update(['status' => BillingSetupItem::STATUS_COMPLETED, 'next_bill_date' => null]);

            return;
        }

        $item->update([
            'status' => BillingSetupItem::STATUS_ACTIVE,
            'next_bill_date' => $next->toDateString(),
        ]);
    }

    /**
     * Move the setup to the next cycle; complete it when every item is done.
     */
    private function advanceSetup(BillingSetup $setup, Carbon $nextDate): void
    {
        $hasBillableItems = $setup->items()
            ->where('status', '!=', BillingSetupItem::STATUS_COMPLETED)
            ->exists();

        $setup->update([
            'next_invoice_date' => $nextDate->toDateString(),
            'status' => $hasBillableItems ? $setup->status : BillingSetup::STATUS_COMPLETED,
        ]);
    }

    /**
     * Next cycle date for a frequency.
     */
    private function advance(Carbon $date, string $frequency): Carbon
    {
        $next = $date->copy();

        return match ($frequency) {
            'weekly' => $next->addWeek(),
            'quarterly' => $next->addMonths(3),
            'yearly' => $next->addYear(),
            default => $next->addMonth(), // monthly
        };
    }
}
