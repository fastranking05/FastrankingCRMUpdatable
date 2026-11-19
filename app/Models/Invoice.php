<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $table = 'invoices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_SENT = 'Invoice Sent';

    public const STATUS_PARTIAL = 'Partially Paid';

    public const STATUS_PAID = 'Paid';

    public const STATUS_OVERDUE = 'Overdue';

    public const STATUS_CANCELLED = 'Cancelled';

    protected $fillable = [
        'id',
        'billing_setup_id',
        'contract_id',
        'proposal_id',
        'followup_business_id',
        'invoice_date',
        'due_date',
        'buffer_until',
        'period_start',
        'period_end',
        'subtotal',
        'vat_amount',
        'total_amount',
        'previous_balance',
        'grand_total',
        'amount_paid',
        'balance',
        'status',
        'sent_at',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'buffer_until' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'subtotal' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'previous_balance' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance' => 'decimal:2',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Invoice $model) {
            if (empty($model->id)) {
                $model->id = static::generateCustomId();
            }
        });
    }

    public static function generateCustomId(): string
    {
        $prefix = 'FRIN';
        $padding = 8;

        $latest = static::orderBy('id', 'desc')->first();

        $nextNumber = $latest
            ? ((int) substr($latest->id, strlen($prefix))) + 1
            : 1;

        return $prefix.str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);
    }

    /**
     * Recalculate amount_paid/balance/status from recorded payments.
     */
    public function recalculatePayments(): void
    {
        $this->amount_paid = round((float) $this->payments()->sum('amount'), 2);
        $this->balance = round((float) $this->grand_total - (float) $this->amount_paid, 2);

        if ($this->status !== self::STATUS_CANCELLED) {
            if ($this->balance <= 0) {
                $this->status = self::STATUS_PAID;
                $this->paid_at = $this->paid_at ?? now();
            } elseif ($this->amount_paid > 0) {
                $this->status = self::STATUS_PARTIAL;
            }
        }

        $this->save();
    }

    public function billingSetup(): BelongsTo
    {
        return $this->belongsTo(BillingSetup::class, 'billing_setup_id');
    }

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
        return $this->hasMany(InvoiceItem::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class, 'invoice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
