<?php

namespace Database\Factories;

use App\Models\FollowupBusiness;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FollowupBusiness>
 */
class FollowupBusinessFactory extends Factory
{
    protected $model = FollowupBusiness::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'category' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Education']),
            'type' => $this->faker->randomElement(['Standard', 'Premium', 'Enterprise']),
            'website' => $this->faker->url(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'created_by' => User::factory(),
        ];
    }
}
