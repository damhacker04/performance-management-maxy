<?php

namespace Database\Factories;

use App\Models\MonthlyTarget;
use App\Models\User;
use App\Models\WeeklyTarget;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeeklyTargetFactory extends Factory
{
    protected $model = WeeklyTarget::class;

    public function definition(): array
    {
        return [
            'monthly_target_id' => MonthlyTarget::factory(),
            'user_id' => User::factory(),
            'assigned_to' => null,
            'category' => 'planned',
            'impact_level' => 'medium',
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'target_type' => 'quantitative',
            'target_value' => fake()->numberBetween(1, 100),
            'target_unit' => 'leads',
            'week_number' => fake()->numberBetween(1, 4),
            'month' => (int) now()->month,
            'year' => (int) now()->year,
        ];
    }

    public function forMonthly(MonthlyTarget $mt): static
    {
        return $this->state([
            'monthly_target_id' => $mt->id,
            'user_id' => $mt->user_id,
            'month' => $mt->month,
            'year' => $mt->year,
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(['assigned_to' => $user->id]);
    }

    public function impact(string $level): static
    {
        return $this->state(['impact_level' => $level]);
    }
}
