<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'status',
        'custom_status',
        'reason',
        'meeting_date',
        'meeting_slot',
        'closer',
        'conducted_date',
        'assigned_user',
        'meeting_link',
        'is_customer_available',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'conducted_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the appointment that this consultation belongs to
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    /**
     * Get the time slot for meeting
     */
    public function meetingSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class, 'meeting_slot');
    }

    /**
     * Get the user who closed this consultation.
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closer');
    }

    /**
     * Get the user assigned to this consultation
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user');
    }

    /**
     * Get the user who created this consultation
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all consultations for a specific appointment
     */
    public static function forAppointment(string $appointmentId): HasMany
    {
        return static::where('appointment_id', $appointmentId);
    }
}
