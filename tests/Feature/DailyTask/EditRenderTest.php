<?php

namespace Tests\Feature\DailyTask;

use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_edit_page_for_pending_report(): void
    {
        $staff = User::factory()->staff()->department('sales')->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->create();

        $this->actingAs($staff)->get(route('daily-tasks.edit', $entry))
            ->assertOk()
            ->assertSee('Edit Laporan');
    }

    public function test_owner_can_open_edit_page_for_revision_report(): void
    {
        $staff = User::factory()->staff()->department('sales')->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->revision()->create([
            'rejection_note' => 'Mohon lengkapi bukti pendukung.',
        ]);

        $this->actingAs($staff)->get(route('daily-tasks.edit', $entry))
            ->assertOk()
            ->assertSee('Balasan Revisi untuk Leader');
    }

    public function test_non_owner_cannot_open_edit_page(): void
    {
        $owner    = User::factory()->staff()->department('sales')->create();
        $other    = User::factory()->staff()->department('sales')->create();
        $entry    = DailyTaskEntry::factory()->forUser($owner)->create();

        $this->actingAs($other)->get(route('daily-tasks.edit', $entry))
            ->assertForbidden();
    }
}
