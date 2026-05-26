<?php

use App\Models\FollowupBusiness;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Denormalized columns for cursor-friendly ORDER BY on follow-up list APIs.
     */
    public function up(): void
    {
        Schema::table('followup_businesses', function (Blueprint $table) {
            $table->date('latest_followup_date')->nullable()->after('created_by');
            $table->time('latest_followup_time')->nullable()->after('latest_followup_date');

            $table->index([
                'latest_followup_date',
                'latest_followup_time',
                'created_at',
                'id',
            ], 'fb_latest_followup_sort_idx');
        });

        FollowupBusiness::query()
            ->whereHas('followupDetails')
            ->orderBy('id')
            ->chunkById(500, function ($businesses): void {
                foreach ($businesses as $business) {
                    FollowupBusiness::refreshLatestFollowupSortFromDetails($business->id);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('followup_businesses', function (Blueprint $table) {
            $table->dropIndex('fb_latest_followup_sort_idx');
            $table->dropColumn(['latest_followup_date', 'latest_followup_time']);
        });
    }
};
