<?php

namespace Database\Seeders;

use App\Models\AppointmentSetting;
use App\Models\TimeSlot;
use Illuminate\Database\Seeder;

class AppointmentSystemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Initialize default appointment settings
        $this->initializeAppointmentSettings();

        // Create default time slots
        $this->createDefaultTimeSlots();
    }

    /**
     * Initialize default appointment settings
     */
    private function initializeAppointmentSettings(): void
    {
        AppointmentSetting::initializeDefaults();
    }

    /**
     * Create default time slots
     */
    private function createDefaultTimeSlots(): void
    {
        $defaultSlots = [
            [
                'name' => 'Morning Slot 1',
                'start_time' => '09:00:00',
                'end_time' => '09:30:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 3,
                'description' => 'Early morning consultation slot',
            ],
            [
                'name' => 'Morning Slot 2',
                'start_time' => '09:30:00',
                'end_time' => '10:00:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 3,
                'description' => 'Mid-morning consultation slot',
            ],
            [
                'name' => 'Morning Slot 3',
                'start_time' => '10:00:00',
                'end_time' => '10:30:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 3,
                'description' => 'Late morning consultation slot',
            ],
            [
                'name' => 'Morning Slot 4',
                'start_time' => '10:30:00',
                'end_time' => '11:00:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 3,
                'description' => 'End of morning consultation slot',
            ],
            [
                'name' => 'Break Time',
                'start_time' => '11:00:00',
                'end_time' => '11:30:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 2,
                'description' => 'Coffee break and quick consultation slot',
            ],
            [
                'name' => 'Late Morning Slot',
                'start_time' => '11:30:00',
                'end_time' => '12:00:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 3,
                'description' => 'Pre-lunch consultation slot',
            ],
            [
                'name' => 'Lunch Break',
                'start_time' => '12:00:00',
                'end_time' => '13:00:00',
                'duration_minutes' => 60,
                'max_concurrent_bookings' => 1,
                'description' => 'Lunch hour - limited appointments',
            ],
            [
                'name' => 'Afternoon Slot 1',
                'start_time' => '13:00:00',
                'end_time' => '13:30:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 3,
                'description' => 'Early afternoon consultation slot',
            ],
            [
                'name' => 'Afternoon Slot 2',
                'start_time' => '13:30:00',
                'end_time' => '14:00:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 3,
                'description' => 'Mid-afternoon consultation slot',
            ],
            [
                'name' => 'Afternoon Slot 3',
                'start_time' => '14:00:00',
                'end_time' => '14:30:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 3,
                'description' => 'Late afternoon consultation slot',
            ],
            [
                'name' => 'Afternoon Slot 4',
                'start_time' => '14:30:00',
                'end_time' => '15:00:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 3,
                'description' => 'End of afternoon consultation slot',
            ],
            [
                'name' => 'Tea Break',
                'start_time' => '15:00:00',
                'end_time' => '15:30:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 2,
                'description' => 'Tea break and quick consultation slot',
            ],
            [
                'name' => 'Late Afternoon Slot 1',
                'start_time' => '15:30:00',
                'end_time' => '16:00:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 3,
                'description' => 'Late afternoon consultation slot',
            ],
            [
                'name' => 'Late Afternoon Slot 2',
                'start_time' => '16:00:00',
                'end_time' => '16:30:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 3,
                'description' => 'End of day consultation slot',
            ],
            [
                'name' => 'Extended Hours Slot',
                'start_time' => '16:30:00',
                'end_time' => '17:00:00',
                'duration_minutes' => 30,
                'max_concurrent_bookings' => 2,
                'description' => 'Extended hours consultation slot',
            ],
        ];

        foreach ($defaultSlots as $slotData) {
            TimeSlot::create($slotData);
        }
    }
}
