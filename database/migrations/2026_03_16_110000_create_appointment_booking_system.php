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
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // e.g., "Morning Slot", "Afternoon Slot"
            $table->time('start_time'); // e.g., 09:00:00
            $table->time('end_time');   // e.g., 09:30:00
            $table->integer('duration_minutes'); // e.g., 30
            $table->boolean('is_active')->default(true);
            $table->integer('max_concurrent_bookings')->default(3); // Max appointments per slot
            $table->text('description')->nullable();
            $table->json('department_ids')->nullable(); // Which departments can use this slot
            $table->timestamps();
            
            $table->index(['is_active', 'start_time']);
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->string('id', 12)->primary(); // FRMID00000001 format
            $table->unsignedBigInteger('followup_business_id');
            $table->string('source', 255)->nullable();
            $table->enum('status', ['Appointment Booked', 'Appointment Rebooked'])->default('Appointment Booked');
            $table->date('date');
            $table->unsignedBigInteger('time_slot_id');
            $table->string('current_status', 100)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            
            $table->foreign('followup_business_id')->references('id')->on('followup_businesses')->onDelete('cascade');
            $table->foreign('time_slot_id')->references('id')->on('time_slots')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            
            $table->unique(['followup_business_id', 'date', 'time_slot_id'], 'appointment_business_date_slot_unique');
            $table->index(['date', 'time_slot_id', 'status']);
            $table->index(['current_status']);
        });

        Schema::create('appointment_temporary_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_id', 12)->nullable();
            $table->date('date');
            $table->unsignedBigInteger('time_slot_id');
            $table->unsignedBigInteger('user_id');
            $table->string('session_id', 255); // Browser session identifier
            $table->timestamp('expires_at'); // 15 minutes from creation
            $table->timestamps();
            
            $table->foreign('time_slot_id')->references('id')->on('time_slots')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['date', 'time_slot_id', 'session_id'], 'temp_booking_unique');
            $table->index(['expires_at']);
        });

        Schema::create('appointment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['key', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_settings');
        Schema::dropIfExists('appointment_temporary_bookings');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('time_slots');
    }
};
