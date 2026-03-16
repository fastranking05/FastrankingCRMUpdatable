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
        'designation',
        'gender',
        'dob',
        'primaryphone',
        'altphone',
        'primarymobile',
        'altmobile',
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

    protected $hidden = [
        'primaryphone',
        'altphone',
        'primarymobile',
        'altmobile',
        'altemail',
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
}
