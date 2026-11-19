<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillingSetup extends Model
{
    use HasFactory;

    protected $table = 'billing_setups';

    public const STATUS_ACTIVE = 'Active';

    public const STATUS_PAUSED = 'Paused';

    public const STATUS_COMPLETED = 'Completed';

    public const FREQUENCIES = ['weekly', 'monthly', 'quarterly', 'yearly'];

    protected $fillable = [
        'contract_id',
        'proposal_id',
        'followup_business_id',
        'invoice_frequency',
        'billing_day',
        'billing_start_date',
        'due_days',
        'buffer_days',
        'next_invoice_date',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'billing_start_date' => 'date',
        'next_invoice_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
    }

    public function followupBusiness(): BelongsTo
    {
        return $this->belongsTo(FollowupBusiness::class, 'followup_business_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillingSetupItem::class, 'billing_setup_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'billing_setup_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
