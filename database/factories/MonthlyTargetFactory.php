<?php

namespace Database\Factories;

use App\Models\MonthlyTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonthlyTargetFactory extends Factory
{
    protected $model = MonthlyTarget::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department' => 'product_it',
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'month' => (int) now()->month,
            'year' => (int) now()->year,
            'assigned_to' => null,
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(['assigned_to' => $user->id, 'department' => $user->department]);
    }

    public function period(int $month, int $year): static
    {
        return $this->state(['month' => $month, 'year' => $year]);
    }
}
