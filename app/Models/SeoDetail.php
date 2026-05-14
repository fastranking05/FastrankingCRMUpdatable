<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeoDetail extends Model
{
    use HasFactory;

    protected $table = 'seo_details';

    protected $fillable = [
        'followup_business_id',
        'status',
        'reason',
        'audited_website',
        'audited_date',
        'auditor',
        'assigned_user',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function followupBusiness(): BelongsTo
    {
        return $this->belongsTo(FollowupBusiness::class, 'followup_business_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user');
    }

    public function questionAnswers(): HasMany
    {
        return $this->hasMany(SeoQuestionAnswer::class, 'seo_details_id');
    }
}
