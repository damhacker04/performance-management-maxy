<?php

namespace Tests\Feature\DailyTask;

use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyTaskGuardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_link_evidence_must_be_a_valid_url(): void
    {
        $staff = User::factory()->staff()->create();

        $response = $this->actingAs($staff)->post(route('daily-tasks.store'), [
            'task_description' => 'Tugas dengan bukti link tidak valid',
            'priority' => 'medium',
            'duration_value' => 30,
            'duration_unit' => 'menit',
            'status' => 'dalam_proses',
            'notes' => 'Catatan singkat.',
            'evidences' => [
                ['type' => 'link', 'label' => 'Bukti', 'path_or_url' => ['bukan-url']],
            ],
        ]);

        $response->assertSessionHasErrors('evidences.0.path_or_url.0');
    }

    public function test_valid_link_evidence_is_accepted(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->post(route('daily-tasks.store'), [
            'task_description' => 'Tugas dengan bukti link valid',
            'priority' => 'medium',
            'duration_value' => 30,
            'duration_unit' => 'menit',
            'status' => 'dalam_proses',
            'notes' => 'Catatan singkat.',
            'evidences' => [
                ['type' => 'link', 'label' => 'Bukti', 'path_or_url' => ['https://example.com/doc']],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('daily_task_evidences', [
            'type' => 'link',
            'path_or_url' => 'https://example.com/doc',
        ]);
    }

    public function test_approved_report_can_still_be_marked_complete(): void
    {

        $staff = User::factory()->staff()->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->approved()->status('dalam_proses')->create();

        $this->actingAs($staff)
            ->patch(route('daily-tasks.complete', $entry))
            ->assertRedirect(route('daily-tasks.show', $entry));

        $fresh = $entry->fresh();
        $this->assertSame('selesai', $fresh->status);

        $this->assertSame('approved', $fresh->verification_status);
    }

    public function test_approved_report_without_notes_completes_directly(): void
    {

        $staff = User::factory()->staff()->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->approved()->status('dalam_proses')
            ->state(['notes' => null])->create();

        $this->actingAs($staff)
            ->patch(route('daily-tasks.complete', $entry))
            ->assertRedirect(route('daily-tasks.show', $entry));

        $this->assertSame('selesai', $entry->fresh()->status);
    }

    public function test_rejected_report_cannot_be_marked_complete(): void
    {
        $staff = User::factory()->staff()->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->verification('rejected')->status('dalam_proses')->create();

        $this->actingAs($staff)
            ->patch(route('daily-tasks.complete', $entry))
            ->assertRedirect();

        $this->assertSame('dalam_proses', $entry->fresh()->status);
    }

    public function test_complete_without_notes_redirects_to_edit_when_not_verified(): void
    {
        $staff = User::factory()->staff()->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->status('dalam_proses')
            ->state(['notes' => null])->create();

        $this->actingAs($staff)
            ->patch(route('daily-tasks.complete', $entry))
            ->assertRedirect(route('daily-tasks.edit', $entry));

        $this->assertSame('dalam_proses', $entry->fresh()->status);
    }

    public function test_user_cannot_complete_someone_elses_report(): void
    {
        $owner = User::factory()->staff()->create();
        $other = User::factory()->staff()->create();
        $entry = DailyTaskEntry::factory()->forUser($owner)->status('dalam_proses')->create();

        $this->actingAs($other)
            ->patch(route('daily-tasks.complete', $entry))
            ->assertForbidden();
    }

    public function test_user_cannot_edit_someone_elses_report(): void
    {
        $owner = User::factory()->staff()->create();
        $other = User::factory()->staff()->create();
        $entry = DailyTaskEntry::factory()->forUser($owner)->create();

        $this->actingAs($other)
            ->get(route('daily-tasks.edit', $entry))
            ->assertForbidden();
    }
}
