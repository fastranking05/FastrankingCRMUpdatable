<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Quality extends Model
{
    use HasFactory;

    protected $table = 'qualities';

    protected $fillable = [
        'appointment_id',
        'auditstatus',
        'status',
        'assigned_user',
        'meeting_link',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id', 'id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QualityAnswer::class, 'quality_id');
    }
}
