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
        Schema::create('billing_setups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->unique()->constrained('contracts')->cascadeOnDelete();
            $table->string('proposal_id', 12);
            $table->foreign('proposal_id')->references('id')->on('proposals')->cascadeOnDelete();
            $table->foreignId('followup_business_id')->constrained('followup_businesses')->cascadeOnDelete();
            $table->string('invoice_frequency', 20); // weekly, monthly, quarterly, yearly
            $table->unsignedTinyInteger('billing_day')->nullable(); // day-of-month (monthly+) or day-of-week 1-7 (weekly)
            $table->date('billing_start_date');
            $table->unsignedSmallInteger('due_days')->default(7); // days after invoice_date for due_date
            $table->unsignedSmallInteger('buffer_days')->default(0); // grace days after due_date before Overdue
            $table->date('next_invoice_date');
            $table->string('status', 30)->default('Active'); // Active, Paused, Completed
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
        Schema::dropIfExists('billing_setups');
    }
};
