<?php

namespace Database\Factories;

use App\Models\AiEvaluation;
use App\Models\DailyTaskEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class AiEvaluationFactory extends Factory
{
    protected $model = AiEvaluation::class;

    public function definition(): array
    {
        return [
            'daily_task_entry_id' => DailyTaskEntry::factory(),
            'score_achievement' => 7,
            'score_efficiency' => 7,
            'score_contribution' => 7,
            'score_problem_solving' => 7,
            'final_score' => 7,
            'ai_feedback' => fake()->sentence(),
            'link_status' => 'no_link',
            'is_overridden' => false,
            'raw_response' => ['score_achievement' => 7],
        ];
    }

    public function forEntry(DailyTaskEntry $entry): static
    {
        return $this->state(['daily_task_entry_id' => $entry->id]);
    }
}
