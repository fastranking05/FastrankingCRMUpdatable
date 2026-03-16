<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowupComment extends Model
{
    use HasFactory;

    protected $table = 'followup_comments';

    protected $fillable = [
        'followup_detail_id',
        'comment',
        'comment_type',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function followupDetail(): BelongsTo
    {
        return $this->belongsTo(FollowupDetail::class, 'followup_detail_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
