<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentTemporaryBooking extends Model
{
    use HasFactory;

    protected $table = 'appointment_temporary_bookings';

    protected $fillable = [
        'appointment_id',
        'date',
        'time_slot_id',
        'user_id',
        'session_id',
        'expires_at',
    ];

    protected $casts = [
        'date' => 'date',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'time_slot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Check if temporary booking is still valid
    public function isValid(): bool
    {
        return $this->expires_at > now();
    }

    // Clean up expired temporary bookings
    public static function cleanupExpired()
    {
        static::where('expires_at', '<', now())->delete();
    }

    // Create temporary booking
    public static function holdSlot(string $date, int $timeSlotId, int $userId, string $sessionId): self
    {
        // Clean up expired bookings first
        static::cleanupExpired();

        // Check if slot is already held by this session
        $existing = static::where('date', $date)
            ->where('time_slot_id', $timeSlotId)
            ->where('session_id', $sessionId)
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            // Extend the existing booking
            $existing->expires_at = now()->addMinutes(15);
            $existing->save();
            return $existing;
        }

        // Create new temporary booking
        return static::create([
            'date' => $date,
            'time_slot_id' => $timeSlotId,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    // Release temporary booking
    public function release()
    {
        $this->delete();
    }

    // Convert temporary booking to actual appointment
    public function confirmAppointment(array $appointmentData): Appointment
    {
        $appointment = Appointment::create($appointmentData);
        $this->appointment_id = $appointment->id;
        $this->save();
        
        return $appointment;
    }
}
