<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'mobile' => '091'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'role' => User::ROLE_CLIENT,
            'remember_token' => Str::random(10),
        ];
    }

    public function lawyer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_LAWYER,
        ]);
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_CLIENT,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
