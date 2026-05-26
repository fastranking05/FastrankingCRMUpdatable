<?php

namespace App\Observers;

use App\Models\FollowupBusiness;
use App\Models\FollowupDetail;

class FollowupDetailObserver
{
    public function saved(FollowupDetail $followupDetail): void
    {
        FollowupBusiness::refreshLatestFollowupSortFromDetails((int) $followupDetail->followup_business_id);

        if ($followupDetail->wasChanged('followup_business_id')) {
            $previous = $followupDetail->getOriginal('followup_business_id');
            if ($previous) {
                FollowupBusiness::refreshLatestFollowupSortFromDetails((int) $previous);
            }
        }
    }

    public function deleted(FollowupDetail $followupDetail): void
    {
        FollowupBusiness::refreshLatestFollowupSortFromDetails((int) $followupDetail->followup_business_id);
    }
}
