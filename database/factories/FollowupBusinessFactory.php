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
            'company_type' => $this->faker->randomElement(['Private Limited', 'Public Limited', 'LLP', 'Partnership']),
            'category' => $this->faker->randomElement(['Technology', 'Healthcare', 'Finance', 'Education']),
            'type' => $this->faker->randomElement(['Standard', 'Premium', 'Enterprise']),
            'website' => $this->faker->url(),
            'created_by' => User::factory(),
        ];
    }
}
