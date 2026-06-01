<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    use HasFactory;

    protected $table = 'deals';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'followup_business_id',
        'auth_person_id',
        'name',
        'type',
        'deal_stage',
        'lost_reason',
        'probability',
        'estimated_closed_date',
        'selected_service',
        'amount_exc_vat',
        'vat',
        'next_activity',
        'priority',
        'created_by',
    ];

    protected $casts = [
        'probability' => 'decimal:2',
        'estimated_closed_date' => 'date',
        'amount_exc_vat' => 'decimal:2',
        'vat' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Deal $model) {
            if (empty($model->id)) {
                $model->id = static::generateCustomId();
            }
        });
    }

    public static function generateCustomId(): string
    {
        $prefix = 'FRDID';
        $padding = 8;

        $latest = static::orderBy('id', 'desc')->first();

        if ($latest) {
            $numericPart = (int) substr($latest->id, strlen($prefix));
            $nextNumber = $numericPart + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);
    }

    public function followupBusiness(): BelongsTo
    {
        return $this->belongsTo(FollowupBusiness::class, 'followup_business_id');
    }

    public function authPerson(): BelongsTo
    {
        return $this->belongsTo(FollowupAuthPerson::class, 'auth_person_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
