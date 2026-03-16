<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = $this->faker->numberBetween(8, 17);
        $startMinute = $this->faker->randomElement([0, 15, 30, 45]);
        $duration = $this->faker->randomElement([30, 60, 90, 120]);
        
        return [
            'name' => $this->faker->randomElement(['Morning Slot', 'Afternoon Slot', 'Evening Slot', 'Consultation Slot']),
            'start_time' => sprintf('%02d:%02d:00', $startHour, $startMinute),
            'end_time' => sprintf('%02d:%02d:00', $startHour, $startMinute + $duration),
            'duration_minutes' => $duration,
            'is_active' => true,
            'max_concurrent_bookings' => $this->faker->numberBetween(1, 5),
            'description' => $this->faker->optional()->sentence(),
            'department_ids' => $this->faker->optional()->json([1, 2, 3]),
        ];
    }
}
