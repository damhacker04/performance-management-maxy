<?php

namespace Tests\Feature\Admin;

use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;
use App\Models\User;
use App\Models\WeeklyTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_target_assignment(): void
    {

        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->get(route('admin.target-assignment.index'))
            ->assertOk()
            ->assertSee('Distribusi Target Mingguan ke Staf');
    }

    public function test_target_assignment_lists_staff_for_selected_department(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $staff = User::factory()->staff()->create(['department' => 'product_it', 'name' => 'Citra Staf']);

        $this->actingAs($admin)
            ->get(route('admin.target-assignment.index', ['department' => 'product_it']))
            ->assertOk()
            ->assertSee('Citra Staf');
    }

    public function test_leader_cannot_access_target_assignment(): void
    {
        $leader = User::factory()->leader()->create();
        $this->actingAs($leader)->get(route('admin.target-assignment.index'))->assertForbidden();
    }

    public function test_super_admin_can_assign_weekly_target_to_staff(): void
    {
        $admin  = User::factory()->superAdmin()->create();
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);
        $wt     = WeeklyTarget::factory()->forMonthly($mt)->create(['assigned_to' => null]);

        $this->actingAs($admin)->post(route('admin.target-assignment.assign-weekly'), [
            'weekly_target_id' => $wt->id,
            'user_id'          => $staff->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('weekly_targets', ['id' => $wt->id, 'assigned_to' => $staff->id]);
    }

    public function test_assign_weekly_requires_valid_ids(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.target-assignment.assign-weekly'), [
            'weekly_target_id' => 9999,
            'user_id'          => 9999,
        ])->assertSessionHasErrors(['weekly_target_id', 'user_id']);
    }

    public function test_super_admin_can_unassign_weekly_target(): void
    {
        $admin  = User::factory()->superAdmin()->create();
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);
        $wt     = WeeklyTarget::factory()->forMonthly($mt)->assignedTo($staff)->create();

        $this->actingAs($admin)->post(route('admin.target-assignment.unassign-weekly'), [
            'weekly_target_id' => $wt->id,
        ])->assertRedirect();

        $this->assertNull($wt->fresh()->assigned_to);
    }

    public function test_super_admin_can_destroy_monthly_target(): void
    {
        $admin  = User::factory()->superAdmin()->create();
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);

        $this->actingAs($admin)
            ->delete(route('admin.monthly-targets.destroy', $mt))
            ->assertRedirect(route('admin.target-assignment.index'));

        $this->assertDatabaseMissing('monthly_targets', ['id' => $mt->id]);
    }

    public function test_super_admin_can_destroy_weekly_target(): void
    {
        $admin  = User::factory()->superAdmin()->create();
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);
        $wt     = WeeklyTarget::factory()->forMonthly($mt)->create();

        $this->actingAs($admin)
            ->delete(route('admin.weekly-targets.destroy', $wt))
            ->assertRedirect();

        $this->assertDatabaseMissing('weekly_targets', ['id' => $wt->id]);
    }

    public function test_super_admin_can_destroy_daily_task(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $staff = User::factory()->staff()->create();
        $task  = DailyTaskEntry::factory()->forUser($staff)->create();

        $this->actingAs($admin)
            ->delete(route('admin.daily-tasks.destroy', $task))
            ->assertRedirect();

        $this->assertDatabaseMissing('daily_task_entries', ['id' => $task->id]);
    }
}
