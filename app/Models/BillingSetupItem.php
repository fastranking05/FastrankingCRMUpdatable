<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingSetupItem extends Model
{
    use HasFactory;

    protected $table = 'billing_setup_items';

    public const TYPE_ONE_OFF = 'one_off';

    public const TYPE_RECURRING = 'recurring';

    public const STATUS_PENDING = 'Pending';

    public const STATUS_ACTIVE = 'Active';

    public const STATUS_COMPLETED = 'Completed';

    protected $fillable = [
        'billing_setup_id',
        'service_id',
        'billing_type',
        'amount',
        'vat_amount',
        'service_duration',
        'duration_unit',
        'start_date',
        'end_date',
        'next_bill_date',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'service_duration' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_bill_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function billingSetup(): BelongsTo
    {
        return $this->belongsTo(BillingSetup::class, 'billing_setup_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    /**
     * End date from start_date + duration (Days/Weeks/Months/Years).
     * Returns null when duration is missing or the unit is unknown.
     */
    public static function computeEndDate(string $startDate, $duration, ?string $unit): ?string
    {
        if ($duration === null || $duration === '' || !$unit) {
            return null;
        }

        $duration = (float) $duration;
        $date = Carbon::parse($startDate);

        return match (strtolower($unit)) {
            'day', 'days' => $date->addDays((int) round($duration))->toDateString(),
            'week', 'weeks' => $date->addWeeks((int) round($duration))->toDateString(),
            'month', 'months' => $date->addMonths((int) round($duration))->toDateString(),
            'year', 'years' => $date->addYears((int) round($duration))->toDateString(),
            default => null,
        };
    }
}
