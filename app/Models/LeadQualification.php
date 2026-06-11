<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadQualification extends Model
{
    use HasFactory;

    protected $table = 'lead_qualifications';

    protected $fillable = [
        'followup_business_id',
        'temperature',
        'budget',
        'authority',
        'need',
        'timeline',
    ];

    protected $casts = [
        'budget' => 'boolean',
        'authority' => 'boolean',
        'need' => 'boolean',
        'timeline' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Validation rules for the optional lead qualification section on lead create.
     *
     * @return array<string, string>
     */
    public static function validationRules(string $prefix = 'lead_qualification'): array
    {
        $field = fn (string $name) => "{$prefix}.{$name}";

        return [
            $prefix => 'nullable|array',
            $field('temperature') => 'nullable|string|max:255',
            $field('budget') => 'nullable|boolean',
            $field('authority') => 'nullable|boolean',
            $field('need') => 'nullable|boolean',
            $field('timeline') => 'nullable|boolean',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fieldsFromArray(array $data): array
    {
        return [
            'temperature' => $data['temperature'] ?? null,
            'budget' => (bool) ($data['budget'] ?? false),
            'authority' => (bool) ($data['authority'] ?? false),
            'need' => (bool) ($data['need'] ?? false),
            'timeline' => (bool) ($data['timeline'] ?? false),
        ];
    }

    public static function hasPayloadData(array $data): bool
    {
        foreach (['temperature', 'budget', 'authority', 'need', 'timeline'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            if ($value === null || $value === '') {
                continue;
            }

            return true;
        }

        return false;
    }

    public static function createForBusiness(int $followupBusinessId, array $data): self
    {
        return self::create(array_merge(
            ['followup_business_id' => $followupBusinessId],
            self::fieldsFromArray($data)
        ));
    }

    public function followupBusiness(): BelongsTo
    {
        return $this->belongsTo(FollowupBusiness::class, 'followup_business_id');
    }
}
