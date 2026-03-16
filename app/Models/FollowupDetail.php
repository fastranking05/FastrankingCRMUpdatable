<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FollowupDetail extends Model
{
    use HasFactory;

    protected $table = 'followup_details';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = static::generateCustomId();
            }
        });
    }

    public static function generateCustomId(): string
    {
        $prefix = 'FRID';
        $padding = 8;
        
        // Get the latest record
        $latest = static::orderBy('id', 'desc')->first();
        
        if ($latest) {
            // Extract numeric part from latest ID
            $numericPart = (int) substr($latest->id, strlen($prefix));
            $nextNumber = $numericPart + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, $padding, '0', STR_PAD_LEFT);
    }

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
