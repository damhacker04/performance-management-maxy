<?php

namespace Tests\Feature\Ceo;

use App\Models\MonthlyTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CeoTargetPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_ceo_target_index_shows_targets_assigned_to_leaders(): void
    {
        $ceo    = User::factory()->cLevel()->create();
        $leader = User::factory()->leader()->create(['department' => 'creative', 'name' => 'Lead Creative']);

        MonthlyTarget::factory()->assignedTo($leader)->create([
            'user_id' => $ceo->id,
            'title'   => 'Naikkan engagement 20%',
        ]);

        $this->actingAs($ceo)->get(route('ceo.targets.index'))
            ->assertOk()
            ->assertSee('Lead Creative')
            ->assertSee(route('ceo.targets.leader', ['leader' => $leader->id]), false);
    }

    public function test_ceo_leader_detail_shows_ceo_targets_and_staff_targets_readonly(): void
    {
        $ceo    = User::factory()->cLevel()->create();
        $leader = User::factory()->leader()->create(['department' => 'creative']);
        $staff  = User::factory()->staff()->create(['department' => 'creative', 'name' => 'Staf Kreatif']);

        MonthlyTarget::factory()->assignedTo($leader)->create([
            'user_id' => $ceo->id,
            'title'   => 'Target untuk leader',
        ]);

        MonthlyTarget::factory()->assignedTo($staff)->create([
            'user_id' => $leader->id,
            'title'   => 'Target untuk staf',
        ]);

        $this->actingAs($ceo)->get(route('ceo.targets.leader', ['leader' => $leader->id]))
            ->assertOk()
            ->assertSee('Target untuk leader')
            ->assertSee('Staf Kreatif');
    }

    public function test_ceo_leader_detail_rejects_non_leader(): void
    {
        $ceo   = User::factory()->cLevel()->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($ceo)->get(route('ceo.targets.leader', ['leader' => $staff->id]))
            ->assertNotFound();
    }

    public function test_staff_cannot_access_ceo_target_page(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('ceo.targets.index'))->assertForbidden();
    }
}
