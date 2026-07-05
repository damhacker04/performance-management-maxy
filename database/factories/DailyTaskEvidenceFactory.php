<?php

namespace Database\Factories;

use App\Models\DailyTaskEntry;
use App\Models\DailyTaskEvidence;
use Illuminate\Database\Eloquent\Factories\Factory;

class DailyTaskEvidenceFactory extends Factory
{
    protected $model = DailyTaskEvidence::class;

    public function definition(): array
    {
        return [
            'daily_task_entry_id' => DailyTaskEntry::factory(),
            'type' => 'link',
            'label' => fake()->words(2, true),
            'path_or_url' => 'https://docs.google.com/document/d/'.fake()->lexify('??????????').'/edit',
        ];
    }

    public function link(string $url): static
    {
        return $this->state(['type' => 'link', 'path_or_url' => $url]);
    }
}
