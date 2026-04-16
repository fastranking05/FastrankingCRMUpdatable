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
            $table->renameColumn('reschedule_date', 'meeting_date');
            $table->renameColumn('reschedule_slot', 'meeting_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->renameColumn('meeting_date', 'reschedule_date');
            $table->renameColumn('meeting_slot', 'reschedule_slot');
        });
    }
};
