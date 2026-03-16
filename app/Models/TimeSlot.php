<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'duration_minutes',
        'is_active',
        'max_concurrent_bookings',
        'description',
        'department_ids',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'is_active' => 'boolean',
        'department_ids' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'time_slot_id');
    }

    public function temporaryBookings(): HasMany
    {
        return $this->hasMany(AppointmentTemporaryBooking::class, 'time_slot_id');
    }

    // Check if slot is available for specific date and department
    public function isAvailableForDate(string $date, ?int $departmentId = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Check department restriction
        if ($departmentId && $this->department_ids && !in_array($departmentId, $this->department_ids)) {
            return false;
        }

        // Check existing appointments for the date
        $existingAppointments = $this->appointments()
            ->where('date', $date)
            ->whereIn('current_status', ['Booked', 'Confirmed', 'In Progress'])
            ->count();

        // Check temporary bookings (slots being held)
        $tempBookings = $this->temporaryBookings()
            ->where('date', $date)
            ->where('expires_at', '>', now())
            ->count();

        $totalBookings = $existingAppointments + $tempBookings;

        return $totalBookings < $this->max_concurrent_bookings;
    }

    // Get available slots for a specific date and department
    public static function getAvailableSlots(string $date, ?int $departmentId = null): array
    {
        $activeSlots = static::where('is_active', true)
            ->when($departmentId, function ($query, $deptId) {
                $query->where(function ($q) use ($deptId) {
                    $q->whereNull('department_ids')
                      ->orWhereJsonContains('department_ids', $deptId);
                });
            })
            ->orderBy('start_time')
            ->get();

        $availableSlots = [];
        foreach ($activeSlots as $slot) {
            if ($slot->isAvailableForDate($date, $departmentId)) {
                $availableSlots[] = [
                    'id' => $slot->id,
                    'name' => $slot->name,
                    'start_time' => $slot->start_time->format('H:i'),
                    'end_time' => $slot->end_time->format('H:i'),
                    'duration_minutes' => $slot->duration_minutes,
                    'available_slots' => $slot->max_concurrent_bookings - $slot->getCurrentBookingsCount($date),
                ];
            }
        }

        return $availableSlots;
    }

    public function getCurrentBookingsCount(string $date): int
    {
        $appointments = $this->appointments()
            ->where('date', $date)
            ->whereIn('current_status', ['Booked', 'Confirmed', 'In Progress'])
            ->count();

        $tempBookings = $this->temporaryBookings()
            ->where('date', $date)
            ->where('expires_at', '>', now())
            ->count();

        return $appointments + $tempBookings;
    }
}
