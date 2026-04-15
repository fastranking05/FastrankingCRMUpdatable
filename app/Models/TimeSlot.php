<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    /**
     * Get count of active users in Sales department
     */
    public static function getActiveSalesDepartmentUserCount(): int
    {
        try {
            // Debug: Check if Sales department exists first
            $salesDeptExists = DB::table('departments')
                ->where('name', 'Sales')
                ->exists();
            
            if (!$salesDeptExists) {
                \Log::error('Sales department not found in database');
                return 3; // Fallback
            }

            $count = DB::table('users')
                ->join('department_user', 'users.id', '=', 'department_user.user_id')
                ->join('departments', 'department_user.department_id', '=', 'departments.id')
                ->where('departments.name', 'Sales')
                ->where('users.status', 'active')  // Use status column with 'active' value
                ->count();
            
            \Log::info('Active Sales department users count: ' . $count);
            return $count;
            
        } catch (\Exception $e) {
            \Log::error('Error getting active Sales department user count: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return 3; // Default fallback value
        }
    }

    public function getCurrentBookingsCount(string $date): int
    {
        $appointments = $this->appointments()
            ->where('date', $date)
            ->whereIn('current_status', ['Appointment Booked', 'Confirmed', 'In Progress', 'QA-Pending', 'scheduled', 'rescheduled'])
            ->count();

        $tempBookings = $this->temporaryBookings()
            ->where('date', $date)
            ->where('expires_at', '>', now())
            ->count();

        return $appointments + $tempBookings;
    }

    /**
     * Check if time slot is available for a specific date
     */
    public function isAvailableForDate(string $date): bool
    {
        $currentBookings = $this->getCurrentBookingsCount($date);
        $maxBookings = $this->max_concurrent_bookings ?? 3;
        return $currentBookings < $maxBookings;
    }
}
