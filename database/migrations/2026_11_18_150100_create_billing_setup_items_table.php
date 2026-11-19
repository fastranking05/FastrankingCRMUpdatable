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
        Schema::create('billing_setup_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_setup_id')->constrained('billing_setups')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services');
            $table->string('billing_type', 20)->default('recurring'); // one_off, recurring
            $table->decimal('amount', 12, 2)->default(0); // per-cycle charge (one-off = full charge)
            $table->decimal('vat_amount', 12, 2)->default(0); // per-cycle VAT
            $table->decimal('service_duration', 8, 2)->nullable(); // e.g. 4
            $table->string('duration_unit', 50)->nullable(); // Days, Weeks, Months, Years
            $table->date('start_date');
            $table->date('end_date')->nullable(); // start_date + duration; null = open-ended
            $table->date('next_bill_date'); // when this item next bills
            $table->string('status', 30)->default('Pending'); // Pending, Active, Completed
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_setup_items');
    }
};
