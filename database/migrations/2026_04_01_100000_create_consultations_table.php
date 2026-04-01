<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_id', 12); // Foreign key to appointments table
            $table->string('status', 50); // Consultation status
            $table->string('custom_status', 50)->nullable(); // Custom status field
            $table->text('reason')->nullable(); // Reason for consultation
            $table->date('reschedule_date')->nullable(); // Reschedule date
            $table->unsignedBigInteger('reschedule_slot')->nullable(); // Foreign key to time_slots table
            $table->unsignedBigInteger('closer')->nullable(); // Foreign key to users table (who closed consultation)
            $table->date('conducted_date')->nullable(); // When consultation was conducted
            $table->unsignedBigInteger('assigned_user'); // Foreign key to users table (assigned to)
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            
            // Foreign key constraints
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('reschedule_slot')->references('id')->on('time_slots')->onDelete('set null');
            $table->foreign('closer')->references('id')->on('users')->onDelete('set null');
            $table->foreign('assigned_user')->references('id')->on('users');
            
            // Indexes
            $table->index(['appointment_id']);
            $table->index(['status']);
            $table->index(['assigned_user']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};