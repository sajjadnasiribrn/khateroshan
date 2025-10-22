<?php

namespace Database\Factories;

use App\Enums\UserRoleEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'role' => fake()->randomElement(UserRoleEnum::cases()),
            'password' => static::$password ??= Hash::make('Password123!'),
            'remember_token' => Str::random(10),
            'avatar' => fake()->optional()->imageUrl(200, 200, 'people'),
            'last_login' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'preferences' => [
                'language' => fake()->randomElement(['en', 'fa']),
                'timezone' => fake()->timezone(),
            ],
            'skills' => fake()->randomElements([
                'php',
                'javascript',
                'design',
                'management',
                'testing',
                'devops',
            ], fake()->numberBetween(1, 3)),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
