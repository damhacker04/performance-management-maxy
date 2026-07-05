<?php

namespace Tests\Feature\Leader;

use App\Models\MonthlyTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaderTargetViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_view_leader_targets_index(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $this->actingAs($leader)->get(route('leader-targets.index'))->assertOk();
    }

    public function test_staff_cannot_access_leader_targets(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->get(route('leader-targets.index'))->assertForbidden();
    }

    public function test_guest_redirected_from_leader_targets(): void
    {
        $this->get(route('leader-targets.index'))->assertRedirect(route('login'));
    }

    public function test_leader_can_view_c_level_created_target_for_own_dept(): void
    {
        $exec   = User::factory()->cLevel()->create();
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create([
            'department' => 'product_it',
            'user_id'    => $exec->id,
        ]);

        $this->actingAs($leader)->get(route('leader-targets.show', $mt))->assertOk();
    }

    public function test_leader_cannot_view_c_level_target_from_other_dept(): void
    {
        $exec   = User::factory()->cLevel()->create();
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create([
            'department' => 'sales',
            'user_id'    => $exec->id,
        ]);

        $this->actingAs($leader)->get(route('leader-targets.show', $mt))->assertForbidden();
    }

    public function test_leader_cannot_view_own_created_target_via_leader_targets_route(): void
    {

        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create([
            'department' => 'product_it',
            'user_id'    => $leader->id,
        ]);

        $this->actingAs($leader)->get(route('leader-targets.show', $mt))->assertForbidden();
    }
}
