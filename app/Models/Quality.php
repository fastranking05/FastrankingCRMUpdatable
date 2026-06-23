<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Quality extends Model
{
    use HasFactory;

    protected $table = 'qualities';

    protected $fillable = [
        'appointment_id',
        'auditstatus',
        'status',
        'assigned_user',
        'score',
    ];

    protected $casts = [
        'score' => 'decimal:2',
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

    // app/Models/Quality.php

public function scopeFilter($query, array $filters)
{
    // Status filter (e.g., 'status' => 'QA-Approved')
    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    // Audit status filter
    if (!empty($filters['auditstatus'])) {
        $query->where('auditstatus', $filters['auditstatus']);
    }

    // Score range filters
    if (isset($filters['score_min'])) {
        $query->where('score', '>=', $filters['score_min']);
    }
    if (isset($filters['score_max'])) {
        $query->where('score', '<=', $filters['score_max']);
    }
    if (isset($filters['score'])) {
        $query->where('score', $filters['score']);
    }

    // Appointment date filter via relationship
    if (!empty($filters['appointment_date_filter'])) {
        $dateFilter = $filters['appointment_date_filter'];
        $customStart = $filters['custom_start_date'] ?? null;
        $customEnd   = $filters['custom_end_date'] ?? null;

        $query->whereHas('appointment', function ($q) use ($dateFilter, $customStart, $customEnd) {
            switch ($dateFilter) {
                case 'today':
                    $q->whereDate('date', today());
                    break;
                case 'yesterday':
                    $q->whereDate('date', today()->subDay());
                    break;
                case 'this_week':
                    $q->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'last_week':
                    $q->whereBetween('date', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]);
                    break;
                case 'this_month':
                    $q->whereMonth('date', now()->month)->whereYear('date', now()->year);
                    break;
                case 'last_month':
                    $q->whereMonth('date', now()->subMonth()->month)->whereYear('date', now()->subMonth()->year);
                    break;
                case 'this_year':
                    $q->whereYear('date', now()->year);
                    break;
                case 'last_year':
                    $q->whereYear('date', now()->subYear()->year);
                    break;
                case 'custom':
                    if ($customStart && $customEnd) {
                        $q->whereBetween('date', [Carbon::parse($customStart)->startOfDay(), Carbon::parse($customEnd)->endOfDay()]);
                    } elseif ($customStart) {
                        $q->whereDate('date', '>=', Carbon::parse($customStart));
                    } elseif ($customEnd) {
                        $q->whereDate('date', '<=', Carbon::parse($customEnd));
                    }
                    break;
            }
        });
    }
}
}
