<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessService extends Model
{
    use HasFactory;

    protected $table = 'business_services';

    protected $fillable = [
        'followup_business_id',
        'interested_services',
        'primary_service_id',
        'current_agency',
        'current_monthly_spend',
        'planned_monthly_budget',
        'existing_website_platform',
    ];

    protected $casts = [
        'current_monthly_spend' => 'decimal:2',
        'planned_monthly_budget' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function followupBusiness(): BelongsTo
    {
        return $this->belongsTo(FollowupBusiness::class, 'followup_business_id');
    }

    public function primaryService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'primary_service_id');
    }

    /**
     * @return array<int, int>
     */
    public function getInterestedServiceIdsAttribute(): array
    {
        if (empty($this->interested_services)) {
            return [];
        }

        return array_values(array_filter(array_map(
            'intval',
            array_map('trim', explode(',', $this->interested_services))
        )));
    }

    /**
     * @param  array<int, int|string>|null  $serviceIds
     */
    public function setInterestedServiceIdsAttribute(?array $serviceIds): void
    {
        if ($serviceIds === null || $serviceIds === []) {
            $this->attributes['interested_services'] = null;

            return;
        }

        $this->attributes['interested_services'] = implode(',', array_map('intval', $serviceIds));
    }
}
