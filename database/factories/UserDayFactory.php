<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserDay>
 */
class UserDayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'day' => fake()->dayOfWeek(),
            'session' => null,
        ];
    }
}
