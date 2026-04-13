<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBlockCalendar extends Model
{
    protected $table = 'user_block_calender';

    protected $fillable = [
        'user_id',
        'date',
        'slot_id',
        'comments',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who owns this block calendar entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the time slot for this block calendar entry
     */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'slot_id');
    }

    /**
     * Get the user who created this block calendar entry
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
