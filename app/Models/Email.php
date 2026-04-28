<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Email extends Model
{
    protected $fillable = [
        'followup_business_id',
        'to',
        'cc',
        'bcc',
        'type',
        'created_by',
    ];

    protected $casts = [
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
    ];

    /**
     * Get the business that owns the email.
     */
    public function followupBusiness(): BelongsTo
    {
        return $this->belongsTo(FollowupBusiness::class);
    }

    /**
     * Get the user who created the email.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
