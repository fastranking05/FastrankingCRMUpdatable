<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    use HasFactory;

    protected $table = 'proposals';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'business_id',
        'auth_person_id',
        'deal_id',
        'email',
        'service_id',
        'amount',
        'vat_amount',
        'created_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Proposal $model) {
            if (empty($model->id)) {
                $model->id = static::generateCustomId();
            }
        });
    }

    public static function generateCustomId(): string
    {
        $prefix = 'FRPR';
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
        return $this->belongsTo(FollowupBusiness::class, 'business_id');
    }

    public function authPerson(): BelongsTo
    {
        return $this->belongsTo(FollowupAuthPerson::class, 'auth_person_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
