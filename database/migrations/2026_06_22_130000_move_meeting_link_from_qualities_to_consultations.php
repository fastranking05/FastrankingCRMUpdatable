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
        Schema::table('consultations', function (Blueprint $table) {
            $table->string('meeting_link')->nullable()->after('assigned_user');
        });

        $qualitiesWithLinks = DB::table('qualities')
            ->whereNotNull('meeting_link')
            ->where('meeting_link', '!=', '')
            ->get(['appointment_id', 'meeting_link']);

        foreach ($qualitiesWithLinks as $quality) {
            $consultationId = DB::table('consultations')
                ->where('appointment_id', $quality->appointment_id)
                ->orderByDesc('id')
                ->value('id');

            if ($consultationId) {
                DB::table('consultations')
                    ->where('id', $consultationId)
                    ->update(['meeting_link' => $quality->meeting_link]);
            }
        }

        Schema::table('qualities', function (Blueprint $table) {
            $table->dropColumn('meeting_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qualities', function (Blueprint $table) {
            $table->string('meeting_link')->nullable()->after('assigned_user');
        });

        $consultationsWithLinks = DB::table('consultations')
            ->whereNotNull('meeting_link')
            ->where('meeting_link', '!=', '')
            ->get(['appointment_id', 'meeting_link']);

        foreach ($consultationsWithLinks as $consultation) {
            $qualityId = DB::table('qualities')
                ->where('appointment_id', $consultation->appointment_id)
                ->orderByDesc('id')
                ->value('id');

            if ($qualityId) {
                DB::table('qualities')
                    ->where('id', $qualityId)
                    ->update(['meeting_link' => $consultation->meeting_link]);
            }
        }

        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn('meeting_link');
        });
    }
};
