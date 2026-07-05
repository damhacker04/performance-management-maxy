<?php

namespace Tests\Feature\Jobs;

use App\Jobs\EvaluateDailyTaskJob;
use App\Models\AiEvaluation;
use App\Models\DailyTaskEntry;
use App\Models\DailyTaskEvidence;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\LinkExtractorService;
use App\Services\LinkValidatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class EvaluateDailyTaskJobTest extends TestCase
{
    use RefreshDatabase;

    private function aiResult(): array
    {
        return [
            'score_achievement' => 8,
            'score_efficiency' => 7,
            'score_contribution' => 9,
            'score_problem_solving' => 6,
            'ai_feedback' => 'Kerja bagus.',
        ];
    }

    public function test_job_reads_public_evidence_link_and_passes_content_to_ai(): void
    {
        $staff = User::factory()->staff()->department('sales')->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->create();
        DailyTaskEvidence::factory()->for($entry, 'entry')->create([
            'type' => 'link',
            'path_or_url' => 'https://docs.google.com/document/d/abc/edit',
        ]);

        $validator = Mockery::mock(LinkValidatorService::class);
        $validator->shouldReceive('check')->andReturn(['status' => 'public', 'message' => 'ok']);

        $extractor = Mockery::mock(LinkExtractorService::class);
        $extractor->shouldReceive('extract')->andReturn('ISI DOKUMEN BUKTI');

        $gemini = Mockery::mock(GeminiService::class);
        $gemini->shouldReceive('evaluateDailyTask')
            ->once()
            ->withArgs(function ($taskData, $dept, $impact, $weights, $linkContent) {
                return $linkContent === 'ISI DOKUMEN BUKTI';
            })
            ->andReturn($this->aiResult());

        (new EvaluateDailyTaskJob($entry->id))->handle($gemini, $validator, $extractor);

        $this->assertDatabaseHas('ai_evaluations', [
            'daily_task_entry_id' => $entry->id,
            'link_status' => 'public',
        ]);
    }

    public function test_job_is_idempotent_when_run_twice(): void
    {
        $staff = User::factory()->staff()->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->create();

        $validator = Mockery::mock(LinkValidatorService::class);
        $extractor = Mockery::mock(LinkExtractorService::class);

        $gemini = Mockery::mock(GeminiService::class);
        $gemini->shouldReceive('evaluateDailyTask')->andReturn($this->aiResult());

        (new EvaluateDailyTaskJob($entry->id))->handle($gemini, $validator, $extractor);
        (new EvaluateDailyTaskJob($entry->id))->handle($gemini, $validator, $extractor);

        $this->assertSame(1, AiEvaluation::where('daily_task_entry_id', $entry->id)->count());
    }
}
