<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowupDetail extends Model
{
    use HasFactory;

    protected $table = 'followup_details';

    protected $fillable = [
        'followup_business_id',
        'source',
        'status',
        'date',
        'time',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i:s',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(FollowupBusiness::class, 'followup_business_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessor for formatted date and time
    public function getFormattedDateTimeAttribute(): string
    {
        if ($this->date && $this->time) {
            return $this->date->format('Y-m-d') . ' ' . $this->time->format('H:i');
        }
        return $this->date?->format('Y-m-d') ?? '';
    }
}
