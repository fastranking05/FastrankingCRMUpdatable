<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id', 12)->primary(); // FRINV00000001
            $table->foreignId('billing_setup_id')->constrained('billing_setups')->cascadeOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->string('proposal_id', 12);
            $table->foreign('proposal_id')->references('id')->on('proposals')->cascadeOnDelete();
            $table->foreignId('followup_business_id')->constrained('followup_businesses')->cascadeOnDelete();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->date('buffer_until')->nullable(); // due_date + buffer_days
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0); // subtotal + vat
            $table->decimal('previous_balance', 12, 2)->default(0); // unpaid balance carried from prior invoices
            $table->decimal('grand_total', 12, 2)->default(0); // total_amount + previous_balance
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('balance', 12, 2)->default(0); // grand_total - amount_paid
            $table->string('status', 30)->default('Pending'); // Pending, Invoice Sent, Partially Paid, Paid, Overdue, Cancelled
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
