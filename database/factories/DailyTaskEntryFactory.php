<?php

namespace Database\Factories;

use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailyTaskEntryFactory extends Factory
{
    protected $model = DailyTaskEntry::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'monthly_target_id' => null,
            'weekly_target_id' => null,
            'parent_entry_id' => null,
            'task_description' => fake()->sentence(8),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'duration_minutes' => fake()->numberBetween(15, 480),
            'status' => 'dalam_proses',
            'notes' => fake()->sentence(),
            'task_date' => now()->toDateString(),
            'verification_status' => 'pending',
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }

    public function status(string $status): static
    {
        return $this->state(['status' => $status]);
    }

    public function verification(string $status): static
    {
        return $this->state(['verification_status' => $status]);
    }

    public function approved(): static
    {
        return $this->state([
            'verification_status' => 'approved',
            'verified_at' => now(),
        ]);
    }

    public function revision(?\DateTimeInterface $reviewedAt = null): static
    {
        return $this->state([
            'verification_status' => 'revision',
            'reviewed_at' => $reviewedAt ?? now(),
        ]);
    }

    public function on(string $date): static
    {
        return $this->state(['task_date' => $date]);
    }
}
