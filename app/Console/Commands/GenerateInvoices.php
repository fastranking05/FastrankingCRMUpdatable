<?php

namespace App\Console\Commands;

use App\Services\Billing\InvoiceGenerationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateInvoices extends Command
{
    protected $signature = 'invoices:generate {--date= : Run as-of this date (Y-m-d, defaults to today)}';

    protected $description = 'Generate invoices for billing setups whose next_invoice_date has arrived, and mark overdue invoices';

    public function handle(InvoiceGenerationService $service): int
    {
        $asOf = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::today();

        $generated = $service->generateDue($asOf);
        $overdue = $service->markOverdue($asOf);

        $this->info('Invoices generated: '.count($generated));
        foreach ($generated as $id) {
            $this->line('  - '.$id);
        }
        $this->info('Invoices marked overdue: '.$overdue);

        return self::SUCCESS;
    }
}
