<?php

namespace App\Console\Commands;

use App\Models\FollowupBusiness;
use Illuminate\Console\Command;

class SyncFollowupLatestFollowupSortCommand extends Command
{
    protected $signature = 'followup:sync-latest-sort 
                            {--chunk=500 : Chunk size when scanning businesses that have details}';

    protected $description = 'Recompute followup_businesses.latest_followup_date/time from followup_details (after bulk inserts, etc.)';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));

        FollowupBusiness::query()
            ->whereHas('followupDetails')
            ->orderBy('id')
            ->chunkById($chunkSize, function ($businesses): void {
                foreach ($businesses as $business) {
                    FollowupBusiness::refreshLatestFollowupSortFromDetails((int) $business->id);
                }
            });

        $this->info('Synced latest_followup_date/time on businesses with details.');

        return self::SUCCESS;
    }
}
