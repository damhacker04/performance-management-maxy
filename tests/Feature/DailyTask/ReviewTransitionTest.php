<?php

namespace Tests\Feature\DailyTask;

use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_approve_own_department_report(): void
    {
        $leader = User::factory()->leader()->department('sales')->create();
        $staff = User::factory()->staff()->department('sales')->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->create();

        $this->actingAs($leader)
            ->patch(route('daily-tasks.approve', $entry))
            ->assertRedirect();

        $this->assertSame('approved', $entry->fresh()->verification_status);
    }

    public function test_leader_cannot_review_other_department_report(): void
    {
        $leader = User::factory()->leader()->department('sales')->create();
        $staff = User::factory()->staff()->department('finance')->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->create();

        $this->actingAs($leader)
            ->patch(route('daily-tasks.approve', $entry))
            ->assertForbidden();

        $this->assertSame('pending', $entry->fresh()->verification_status);
    }

    public function test_approved_report_cannot_be_rejected_afterwards(): void
    {
        $leader = User::factory()->leader()->department('sales')->create();
        $staff = User::factory()->staff()->department('sales')->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->approved()->create();

        $this->actingAs($leader)
            ->patch(route('daily-tasks.reject', $entry), ['rejection_note' => 'Tidak sesuai sama sekali'])
            ->assertForbidden();

        $this->assertSame('approved', $entry->fresh()->verification_status);
    }

    public function test_staff_cannot_review_reports(): void
    {
        $staff = User::factory()->staff()->department('sales')->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->create();

        $this->actingAs($staff)
            ->patch(route('daily-tasks.approve', $entry))
            ->assertForbidden();
    }
}
