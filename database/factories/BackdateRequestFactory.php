<?php

namespace Database\Factories;

use App\Models\BackdateRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackdateRequestFactory extends Factory
{
    protected $model = BackdateRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'requested_date' => now()->subDay()->toDateString(),
            'reason' => fake()->sentence(10),
            'status' => 'pending',
        ];
    }

    public function approvedWithToken(string $token = 'tok-valid'): static
    {
        return $this->state([
            'status' => 'approved',
            'approval_token' => $token,
            'token_expires_at' => now()->addHours(24),
            'reviewed_at' => now(),
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
