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
        'previous_experience',
        'previous_services',
        'challenges',
        'expectation',
    ];

    protected $casts = [
        'previous_experience' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'interested_service_ids',
        'interested_services_list',
        'previous_service_ids',
        'previous_services_list',
        'challenges_list',
        'expectation_list',
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
            $field('current_monthly_spend') => 'nullable|string|max:30',
            $field('planned_monthly_budget') => 'nullable|string|max:30',
            $field('existing_website_platform') => 'nullable|string|max:255',
            $field('previous_experience') => 'nullable|integer|in:0,1',
            $field('previous_services') => 'nullable|array',
            $field('previous_services.*') => 'integer|exists:services,id',
            $field('challenges') => 'nullable|array',
            $field('challenges.*') => 'nullable|string|max:255',
            $field('expectation') => 'nullable|array',
            $field('expectation.*') => 'nullable|string|max:255',
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

        $previousServices = null;
        if (!empty($data['previous_services']) && is_array($data['previous_services'])) {
            $ids = array_values(array_filter(array_map('intval', $data['previous_services'])));
            $previousServices = $ids !== [] ? implode(',', $ids) : null;
        }

        $challenges = self::commaSeparatedStringFromArray($data['challenges'] ?? null);
        $expectation = self::commaSeparatedStringFromArray($data['expectation'] ?? null);

        return [
            'interested_services' => $interestedServices,
            'primary_service_id' => $data['primary_service_id'] ?? null,
            'current_agency' => $data['current_agency'] ?? null,
            'current_monthly_spend' => $data['current_monthly_spend'] ?? null,
            'planned_monthly_budget' => $data['planned_monthly_budget'] ?? null,
            'existing_website_platform' => $data['existing_website_platform'] ?? null,
            'previous_experience' => array_key_exists('previous_experience', $data)
                ? (int) $data['previous_experience']
                : null,
            'previous_services' => $previousServices,
            'challenges' => $challenges,
            'expectation' => $expectation,
        ];
    }

    /**
     * @param  array<int, mixed>|null  $values
     */
    private static function commaSeparatedStringFromArray(?array $values): ?string
    {
        if ($values === null || $values === []) {
            return null;
        }

        $normalized = array_values(array_filter(
            array_map('trim', array_map('strval', $values)),
            fn (string $value) => $value !== ''
        ));

        return $normalized !== [] ? implode(',', $normalized) : null;
    }

    public static function hasPayloadData(array $data): bool
    {
        foreach (['interested_services', 'primary_service_id', 'current_agency', 'current_monthly_spend', 'planned_monthly_budget', 'existing_website_platform', 'previous_experience', 'previous_services', 'challenges', 'expectation'] as $field) {
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
     * @return array<int, int>
     */
    public function getPreviousServiceIdsAttribute(): array
    {
        if (empty($this->previous_services)) {
            return [];
        }

        return array_values(array_filter(array_map(
            'intval',
            array_map('trim', explode(',', $this->previous_services))
        )));
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function getPreviousServicesListAttribute(): array
    {
        $ids = $this->previous_service_ids;
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
     * @return array<int, string>
     */
    public function getChallengesListAttribute(): array
    {
        return self::commaSeparatedStringToArray($this->challenges);
    }

    /**
     * @return array<int, string>
     */
    public function getExpectationListAttribute(): array
    {
        return self::commaSeparatedStringToArray($this->expectation);
    }

    /**
     * @return array<int, string>
     */
    private static function commaSeparatedStringToArray(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $value)),
            fn (string $item) => $item !== ''
        ));
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
