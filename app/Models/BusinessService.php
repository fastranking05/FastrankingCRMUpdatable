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

    protected $appends = [
        'interested_service_ids',
        'interested_services_list',
    ];

    /**
     * Validation rules for the optional business service section on lead create.
     *
     * @return array<string, string>
     */
    public static function validationRules(string $prefix = 'business_service'): array
    {
        $field = fn (string $name) => "{$prefix}.{$name}";

        return [
            $prefix => 'nullable|array',
            $field('interested_services') => 'nullable|array',
            $field('interested_services.*') => 'integer|exists:services,id',
            $field('primary_service_id') => 'nullable|integer|exists:services,id',
            $field('current_agency') => 'nullable|string|max:255',
            $field('current_monthly_spend') => 'nullable|numeric|min:0',
            $field('planned_monthly_budget') => 'nullable|numeric|min:0',
            $field('existing_website_platform') => 'nullable|string|max:255',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fieldsFromArray(array $data): array
    {
        $interestedServices = null;
        if (!empty($data['interested_services']) && is_array($data['interested_services'])) {
            $ids = array_values(array_filter(array_map('intval', $data['interested_services'])));
            $interestedServices = $ids !== [] ? implode(',', $ids) : null;
        }

        return [
            'interested_services' => $interestedServices,
            'primary_service_id' => $data['primary_service_id'] ?? null,
            'current_agency' => $data['current_agency'] ?? null,
            'current_monthly_spend' => $data['current_monthly_spend'] ?? null,
            'planned_monthly_budget' => $data['planned_monthly_budget'] ?? null,
            'existing_website_platform' => $data['existing_website_platform'] ?? null,
        ];
    }

    public static function hasPayloadData(array $data): bool
    {
        foreach (['interested_services', 'primary_service_id', 'current_agency', 'current_monthly_spend', 'planned_monthly_budget', 'existing_website_platform'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            if ($value === null || $value === '' || $value === []) {
                continue;
            }

            return true;
        }

        return false;
    }

    public static function createForBusiness(int $followupBusinessId, array $data): self
    {
        return self::updateOrCreate(
            ['followup_business_id' => $followupBusinessId],
            self::fieldsFromArray($data)
        );
    }

    public static function upsertForBusiness(int $followupBusinessId, array $data): self
    {
        return self::createForBusiness($followupBusinessId, $data);
    }

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
     * @return array<int, array{id: int, name: string}>
     */
    public function getInterestedServicesListAttribute(): array
    {
        $ids = $this->interested_service_ids;
        if ($ids === []) {
            return [];
        }

        return Service::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
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
