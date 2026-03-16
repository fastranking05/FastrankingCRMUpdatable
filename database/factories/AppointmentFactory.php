<?php

namespace Database\Factories;

use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'followup_business_id' => \App\Models\FollowupBusiness::factory(),
            'source' => $this->faker->randomElement(['Follow-up', 'Direct', 'Referral', 'Website']),
            'status' => $this->faker->randomElement(['Appointment Booked', 'Appointment Rebooked']),
            'date' => $this->faker->dateTimeBetween('+1 week', '+1 month')->format('Y-m-d'),
            'time_slot_id' => TimeSlot::factory(),
            'current_status' => $this->faker->randomElement(['Booked', 'Confirmed', 'In Progress', 'Conducted', 'Not Conducted', 'Rescheduled', 'Cancelled']),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
