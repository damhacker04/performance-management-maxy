<?php

namespace Tests\Feature\Staff;

use App\Models\MonthlyTarget;
use App\Models\User;
use App\Models\WeeklyTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffTargetViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_own_targets_index(): void
    {
        $staff = User::factory()->staff()->create(['department' => 'product_it']);
        $this->actingAs($staff)->get(route('staff-targets.index'))->assertOk();
    }

    public function test_leader_cannot_access_staff_targets_route(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $this->actingAs($leader)->get(route('staff-targets.index'))->assertForbidden();
    }

    public function test_guest_redirected_from_staff_targets(): void
    {
        $this->get(route('staff-targets.index'))->assertRedirect(route('login'));
    }

    public function test_staff_can_view_monthly_target_in_own_department(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create([
            'department' => 'product_it',
            'user_id'    => $leader->id,
        ]);
        WeeklyTarget::factory()->forMonthly($mt)->assignedTo($staff)->create();

        $this->actingAs($staff)->get(route('staff-targets.show', $mt))->assertOk();
    }

    public function test_staff_cannot_view_monthly_target_from_other_department(): void
    {
        $otherLeader = User::factory()->leader()->create(['department' => 'sales']);
        $staff       = User::factory()->staff()->create(['department' => 'product_it']);
        $mt          = MonthlyTarget::factory()->create([
            'department' => 'sales',
            'user_id'    => $otherLeader->id,
        ]);

        $this->actingAs($staff)->get(route('staff-targets.show', $mt))->assertForbidden();
    }

    public function test_staff_cannot_view_c_level_created_target_via_staff_route(): void
    {

        $exec  = User::factory()->cLevel()->create();
        $staff = User::factory()->staff()->create(['department' => 'product_it']);
        $mt    = MonthlyTarget::factory()->create([
            'department' => 'product_it',
            'user_id'    => $exec->id,
        ]);

        $this->actingAs($staff)->get(route('staff-targets.show', $mt))->assertForbidden();
    }
}
