<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'followup_business_id',
        'source',
        'status',
        'date',
        'time_slot_id',
        'current_status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Define possible current statuses
    public const CURRENT_STATUSES = [
        'Booked',
        'Confirmed',
        'In Progress',
        'Conducted',
        'Not Conducted',
        'Rescheduled',
        'Cancelled',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = static::generateCustomId();
            }
            
            // Set default current_status if not provided
            if (empty($model->current_status)) {
                $model->current_status = 'Booked';
            }
        });
    }

    public static function generateCustomId(): string
    {
        $prefix = 'FRMID';
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

    public function followupBusiness(): BelongsTo
    {
        return $this->belongsTo(FollowupBusiness::class, 'followup_business_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Check if user is available for this appointment time
    public function isUserAvailable(): bool
    {
        // Get business department
        $business = $this->followupBusiness;
        
        // Get all users in the same department as the business
        $departmentUsers = User::where('department_id', $business->department_id ?? null)
            ->where('is_active', true)
            ->get();

        $availableUsersCount = 0;
        $maxConcurrent = $this->timeSlot->max_concurrent_bookings ?? 3;

        foreach ($departmentUsers as $user) {
            if ($this->isUserAvailableForAppointment($user)) {
                $availableUsersCount++;
            }
        }

        return $availableUsersCount >= $maxConcurrent;
    }

    private function isUserAvailableForAppointment(User $user): bool
    {
        // Check if user has any conflicting appointments at the same time
        $conflictingAppointments = Appointment::whereHas('followupBusiness', function ($query) use ($user) {
                $query->whereHas('creator', function ($q) use ($user) {
                    $q->where('department_id', $user->department_id);
                });
            })
            ->where('date', $this->date)
            ->where('time_slot_id', $this->time_slot_id)
            ->whereIn('current_status', ['Booked', 'Confirmed', 'In Progress'])
            ->where('id', '!=', $this->id)
            ->count();

        return $conflictingAppointments === 0;
    }

    // Scope for available appointments
    public function scopeAvailable($query, string $date, ?int $departmentId = null)
    {
        return $query->whereHas('timeSlot', function ($slotQuery) use ($date, $departmentId) {
                $slotQuery->where('is_active', true)
                    ->when($departmentId, function ($q, $deptId) {
                        $q->where(function ($subQuery) use ($deptId) {
                            $subQuery->whereNull('department_ids')
                                     ->orWhereJsonContains('department_ids', $deptId);
                        });
                    });
            })
            ->where('date', $date)
            ->whereIn('current_status', ['Booked', 'Confirmed', 'In Progress']);
    }

    // Get formatted time
    public function getFormattedTimeAttribute(): string
    {
        return $this->timeSlot->start_time->format('H:i') . ' - ' . $this->timeSlot->end_time->format('H:i');
    }

    // Check if appointment can be rescheduled
    public function canBeRescheduled(): bool
    {
        return !in_array($this->current_status, ['Conducted', 'Cancelled']);
    }
}
