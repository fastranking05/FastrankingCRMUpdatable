<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentSetting extends Model
{
    use HasFactory;

    protected $table = 'appointment_settings';

    protected $fillable = [
        'key',
        'value',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Default settings
    public static function getDefaultSettings(): array
    {
        return [
            'appointment_booking_timeout' => [
                'value' => '15',
                'description' => 'Temporary booking timeout in minutes',
                'is_active' => true,
            ],
            'max_concurrent_appointments' => [
                'value' => '3',
                'description' => 'Maximum concurrent appointments per time slot',
                'is_active' => true,
            ],
            'allow_rescheduling' => [
                'value' => 'true',
                'description' => 'Allow appointment rescheduling',
                'is_active' => true,
            ],
            'rescheduling_cutoff_hours' => [
                'value' => '24',
                'description' => 'Minimum hours before appointment for rescheduling',
                'is_active' => true,
            ],
            'auto_confirm_appointments' => [
                'value' => 'false',
                'description' => 'Automatically confirm new appointments',
                'is_active' => true,
            ],
            'notification_email_enabled' => [
                'value' => 'true',
                'description' => 'Send email notifications for appointments',
                'is_active' => true,
            ],
            'working_days' => [
                'value' => json_encode(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']),
                'description' => 'Working days for appointments',
                'is_active' => true,
            ],
            'booking_advance_days' => [
                'value' => '30',
                'description' => 'Maximum days in advance for booking',
                'is_active' => true,
            ],
        ];
    }

    // Get setting value
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)
            ->where('is_active', true)
            ->first();

        if ($setting) {
            // Try to decode JSON values
            $decoded = json_decode($setting->value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
        }

        // Check default settings
        $defaults = static::getDefaultSettings();
        if (isset($defaults[$key])) {
            $value = $defaults[$key]['value'];
            return is_string($value) ? json_decode($value, true) ?? $value : $value;
        }

        return $default;
    }

    // Set setting value
    public static function setValue(string $key, $value, string $description = null, bool $isActive = true)
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) ? json_encode($value) : $value,
                'description' => $description,
                'is_active' => $isActive,
            ]
        );
    }

    // Initialize default settings
    public static function initializeDefaults()
    {
        $defaults = static::getDefaultSettings();
        
        foreach ($defaults as $key => $data) {
            static::updateOrCreate(
                ['key' => $key],
                $data
            );
        }
    }

    // Get all active settings
    public static function getAllActive()
    {
        $settings = static::where('is_active', true)->get();
        $result = [];
        
        foreach ($settings as $setting) {
            $decoded = json_decode($setting->value, true);
            $result[$setting->key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $setting->value;
        }
        
        return $result;
    }
}
