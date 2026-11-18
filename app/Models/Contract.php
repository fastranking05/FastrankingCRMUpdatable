<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Contract extends Model
{
    use HasFactory;

    protected $table = 'contracts';

    public const STATUS_PENDING = 'Contract Pending';

    public const STATUS_SENT = 'Contract Sent';

    public const STATUS_SIGNED = 'Contract Signed';

    public const APPOINTMENT_STATUS_ACTIVE = 'Customer Active';

    protected $fillable = [
        'document_id',
        'proposal_id',
        'followup_business_id',
        'document',
        'status',
        'created_by',
    ];

    protected $casts = [
        'document' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Contract $model) {
            if (empty($model->document_id)) {
                $model->document_id = (string) Str::uuid();
            }
        });
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    public function followupBusiness(): BelongsTo
    {
        return $this->belongsTo(FollowupBusiness::class, 'followup_business_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
