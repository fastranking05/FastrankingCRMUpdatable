<?php

namespace App\Support;

use App\Models\FollowupBusiness;
use Illuminate\Database\Eloquent\Model;

class FollowupBusinessProfile
{
    /**
     * @return array<int, string>
     */
    public static function fieldNames(): array
    {
        return [
            'id',
            'name',
            'trading_name',
            'company_registration_number',
            'address_line1',
            'city',
            'postcode',
            'country',
            'company_size',
            'company_type',
            'category',
            'sub_category',
            'type',
            'source_name',
            'sub_source',
            'priority',
            'annual_revenue',
            'number_of_locations',
            'website',
            'created_by',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Mandatory business profile block for single-view API responses.
     *
     * @param  array<string, mixed>  $merge
     * @return array<string, mixed>|null
     */
    public static function forResponse(?FollowupBusiness $business, array $merge = []): ?array
    {
        if ($business === null) {
            return null;
        }

        $business->loadMissing(FollowupBusiness::profileRelations());

        return array_merge([
            ...$business->only(self::fieldNames()),
            'creator' => $business->creator,
            'auth_persons' => $business->authPersons->values()->all(),
            'business_service' => $business->businessService,
            'lead_qualification' => $business->leadQualification,
        ], $merge);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $merge
     * @return array<string, mixed>
     */
    public static function attach(
        array $payload,
        ?FollowupBusiness $business,
        string $key = 'followup_business',
        array $merge = []
    ): array {
        $payload[$key] = self::forResponse($business, $merge);

        unset($payload['followupBusiness']);

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $merge
     * @return array<string, mixed>
     */
    public static function leadShowPayload(FollowupBusiness $business, array $merge = []): array
    {
        return array_merge(self::forResponse($business) ?? [], $merge);
    }

    /**
     * @param  array<int, Model>|array<string, mixed>  $items
     * @return array<int, mixed>|array<string, mixed>
     */
    public static function serializeRelationItems(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => $item instanceof Model ? $item->toArray() : $item)
            ->values()
            ->all();
    }
}
