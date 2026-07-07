<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'staff',
            'department' => 'product_it',
            'is_active' => true,
            'is_management' => false,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function staff(): static
    {
        return $this->state(['role' => 'staff']);
    }

    public function leader(): static
    {
        return $this->state(['role' => 'leader']);
    }

    public function cLevel(): static
    {
        return $this->state(['role' => 'c_level']);
    }

    public function superAdmin(): static
    {
        return $this->state(['role' => 'super_admin']);
    }

    public function management(): static
    {
        return $this->state(['is_management' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function department(string $department): static
    {
        return $this->state(['department' => $department]);
    }
}
