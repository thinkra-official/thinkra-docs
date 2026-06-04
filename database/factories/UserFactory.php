<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => null,
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function teacher(): static
    {
        return $this->state(fn () => [
            'email' => null,
            'phone' => fake()->unique()->numerify('05########'),
            'role' => UserRole::Teacher,
        ]);
    }
}
