<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FollowupBusiness extends Model
{
    use HasFactory;

    protected $table = 'followup_businesses';

    protected $fillable = [
        'name',
        'category',
        'type',
        'website',
        'phone',
        'email',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function authPersons(): BelongsToMany
    {
        return $this->belongsToMany(FollowupAuthPerson::class, 'followup_business_auth_person', 'followup_business_id', 'followup_auth_person_id')
            ->withTimestamps();
    }

    public function followupDetails(): HasMany
    {
        return $this->hasMany(FollowupDetail::class, 'followup_business_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'followup_business_id');
    }
}
