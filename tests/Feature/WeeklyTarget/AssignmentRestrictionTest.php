<?php

namespace Tests\Feature\WeeklyTarget;

use App\Models\MonthlyTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_create_form_lists_leaders_not_staff_for_c_level(): void
    {
        $exec   = User::factory()->cLevel()->create();
        $leader = User::factory()->leader()->create(['department' => 'creative', 'name' => 'Leader Creative Unik']);
        $staff  = User::factory()->staff()->create(['department' => 'creative', 'name' => 'Staff Creative Unik']);

        $this->actingAs($exec)->get(route('monthly-targets.create'))
            ->assertOk()
            ->assertSee('Leader Creative Unik')
            ->assertDontSee('Staff Creative Unik');
    }

    private function weeklyPayload(MonthlyTarget $mt, ?int $assignedTo): array
    {
        return [
            'monthly_target_id' => $mt->id,
            'title'             => 'Target mingguan uji',
            'week_number'       => 1,
            'target_type'       => 'qualitative',
            'assigned_to'       => $assignedTo,
        ];
    }

    public function test_c_level_cannot_assign_weekly_target_to_staff(): void
    {
        $exec  = User::factory()->cLevel()->create();
        $staff = User::factory()->staff()->create(['department' => 'product_it']);
        $mt    = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $exec->id]);

        $this->actingAs($exec)
            ->post(route('weekly-targets.store'), $this->weeklyPayload($mt, $staff->id))
            ->assertSessionHasErrors('assigned_to');

        $this->assertDatabaseMissing('weekly_targets', ['title' => 'Target mingguan uji']);
    }

    public function test_c_level_can_assign_weekly_target_to_leader(): void
    {
        $exec   = User::factory()->cLevel()->create();
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $exec->id]);

        $this->actingAs($exec)
            ->post(route('weekly-targets.store'), $this->weeklyPayload($mt, $leader->id))
            ->assertRedirect();

        $this->assertDatabaseHas('weekly_targets', [
            'title'       => 'Target mingguan uji',
            'assigned_to' => $leader->id,
        ]);
    }

    public function test_leader_can_still_assign_weekly_target_to_staff(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);

        $this->actingAs($leader)
            ->post(route('weekly-targets.store'), $this->weeklyPayload($mt, $staff->id))
            ->assertRedirect();

        $this->assertDatabaseHas('weekly_targets', [
            'title'       => 'Target mingguan uji',
            'assigned_to' => $staff->id,
        ]);
    }
}
