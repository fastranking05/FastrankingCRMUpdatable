<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FollowupAuthPerson extends Model
{
    use HasFactory;

    protected $table = 'followup_auth_persons';

    protected $fillable = [
        'title',
        'firstname',
        'middlename',
        'lastname',
        'is_primary',
        'job_title',
        'seniority_level',
        'extension',
        'linkedin_profile',
        'facebook_profile',
        'preferred_contact_method',
        'preferred_contact_time',
        'gender',
        'dob',
        'primaryphone',
        'primaryphone_country_code',
        'altphone',
        'altphone_country_code',
        'primarymobile',
        'primarymobile_country_code',
        'altmobile',
        'altmobile_country_code',
        'primaryemail',
        'altemail',
        'created_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'dob' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(FollowupBusiness::class, 'followup_business_auth_person', 'followup_auth_person_id', 'followup_business_id')
            ->withTimestamps();
    }

    // Accessor for full name
    public function getFullNameAttribute(): string
    {
        return trim("{$this->title} {$this->firstname} {$this->middlename} {$this->lastname}");
    }

    /**
     * Country code fields for each contact number column.
     *
     * @return list<string>
     */
    public static function phoneCountryCodeFieldNames(): array
    {
        return [
            'primaryphone_country_code',
            'altphone_country_code',
            'primarymobile_country_code',
            'altmobile_country_code',
        ];
    }

    /**
     * Validation rules for phone country code fields.
     *
     * @return array<string, string>
     */
    public static function phoneCountryCodeValidationRules(string $prefix = ''): array
    {
        $field = fn (string $name) => $prefix !== '' ? "{$prefix}.{$name}" : $name;
        $rules = [];

        foreach (self::phoneCountryCodeFieldNames() as $name) {
            $rules[$field($name)] = 'nullable|string|max:10';
        }

        return $rules;
    }

    /**
     * Extract phone country code values from request/array data.
     *
     * @return array<string, mixed>
     */
    public static function phoneCountryCodeFieldsFromArray(array $data): array
    {
        return array_intersect_key($data, array_flip(self::phoneCountryCodeFieldNames()));
    }

    /**
     * Validation rules for optional contact profile fields.
     *
     * @return array<string, string>
     */
    public static function profileFieldValidationRules(string $prefix = ''): array
    {
        $field = fn (string $name) => $prefix !== '' ? "{$prefix}.{$name}" : $name;

        return [
            $field('seniority_level') => 'nullable|string|max:100',
            $field('extension') => 'nullable|string|max:50',
            $field('linkedin_profile') => 'nullable|url|max:255',
            $field('facebook_profile') => 'nullable|url|max:255',
            $field('preferred_contact_method') => 'nullable|string|max:100',
            $field('preferred_contact_time') => 'nullable|string|max:255',
        ];
    }

    /**
     * Extract profile field values from request/array data.
     *
     * @return array<string, mixed>
     */
    public static function profileFieldsFromArray(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'seniority_level',
            'extension',
            'linkedin_profile',
            'facebook_profile',
            'preferred_contact_method',
            'preferred_contact_time',
        ]));
    }

    /**
     * Profile fields for API responses.
     *
     * @return array<string, mixed>
     */
    public function profileFieldsForResponse(): array
    {
        return [
            'seniority_level' => $this->seniority_level,
            'extension' => $this->extension,
            'linkedin_profile' => $this->linkedin_profile,
            'facebook_profile' => $this->facebook_profile,
            'preferred_contact_method' => $this->preferred_contact_method,
            'preferred_contact_time' => $this->preferred_contact_time,
        ];
    }
}
