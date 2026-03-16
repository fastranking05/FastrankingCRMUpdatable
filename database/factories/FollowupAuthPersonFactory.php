<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FollowupAuthPerson>
 */
class FollowupAuthPersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->randomElement(['Mr.', 'Ms.', 'Dr.', 'Prof.']);
        $gender = $title === 'Mr.' || $title === 'Dr.' ? 'male' : 'female';
        
        return [
            'title' => $title,
            'firstname' => $this->faker->firstName($gender),
            'middlename' => $this->faker->optional()->middleName(),
            'lastname' => $this->faker->lastName(),
            'is_primary' => $this->faker->boolean(20), // 20% chance of being primary
            'designation' => $this->faker->jobTitle(),
            'gender' => $gender,
            'dob' => $this->faker->date('Y-m-d', '-18 years'),
            'primaryphone' => $this->faker->unique()->phoneNumber(),
            'altphone' => $this->faker->optional()->phoneNumber(),
            'primarymobile' => $this->faker->unique()->phoneNumber(),
            'altmobile' => $this->faker->optional()->phoneNumber(),
            'primaryemail' => $this->faker->unique()->companyEmail(),
            'altemail' => $this->faker->optional()->email(),
            'created_by' => User::factory(),
        ];
    }
}
