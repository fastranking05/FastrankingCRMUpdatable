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
        Schema::table('consultations', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['appointment_id']);
            
            // Change the appointment_id column to be longer to accommodate FRMID00000002 (13 chars)
            $table->string('appointment_id', 15)->change();
            
            // Re-add foreign key constraint
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['appointment_id']);
            
            // Revert back to original length
            $table->string('appointment_id', 12)->change();
            
            // Re-add foreign key constraint
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
        });
    }
};
