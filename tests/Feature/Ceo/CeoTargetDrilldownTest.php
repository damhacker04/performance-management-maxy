<?php

namespace Tests\Feature\Ceo;

use App\Models\MonthlyTarget;
use App\Models\User;
use App\Models\WeeklyTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CeoTargetDrilldownTest extends TestCase
{
    use RefreshDatabase;

    public function test_ceo_can_open_period_drilldown(): void
    {
        $exec   = User::factory()->cLevel()->create();
        $leader = User::factory()->leader()->create(['department' => 'creative']);

        $mt = MonthlyTarget::factory()->create([
            'department'  => 'creative',
            'assigned_to' => $leader->id,
            'month'       => now()->month,
            'year'        => now()->year,
        ]);
        WeeklyTarget::factory()->create([
            'monthly_target_id' => $mt->id,
            'user_id'           => $leader->id,
            'month'             => now()->month,
            'year'              => now()->year,
        ]);

        $y = now()->year; $m = now()->month;

        $this->actingAs($exec)->followingRedirects()
            ->get(route('monthly-targets.index'))->assertOk();

        $this->actingAs($exec)->get(route('period.staff-list', ['year' => $y, 'month' => $m]))->assertOk();
        $this->actingAs($exec)->get(route('period.staff-targets', ['year' => $y, 'month' => $m, 'staff' => $leader->id]))->assertOk();
        $this->actingAs($exec)->get(route('period.staff-weekly', ['year' => $y, 'month' => $m, 'staff' => $leader->id, 'monthlyTarget' => $mt->id]))->assertOk();
    }

    public function test_ceo_leader_detail_page_links_into_drilldown(): void
    {
        $ceo    = User::factory()->cLevel()->create();
        $leader = User::factory()->leader()->create(['department' => 'creative']);

        $mt = MonthlyTarget::factory()->assignedTo($leader)->create([
            'user_id' => $ceo->id,
            'month'   => now()->month,
            'year'    => now()->year,
        ]);
        $wt = WeeklyTarget::factory()->create([
            'monthly_target_id' => $mt->id,
            'user_id'           => $leader->id,
            'month'             => now()->month,
            'year'              => now()->year,
        ]);

        $drilldownUrl = route('period.weekly-show', [
            'year'          => now()->year,
            'month'         => now()->month,
            'staff'         => $leader->id,
            'monthlyTarget' => $mt->id,
            'weeklyTarget'  => $wt->id,
        ]);

        $this->actingAs($ceo)->get(route('ceo.targets.leader', [
            'leader' => $leader->id,
            'month'  => now()->month,
            'year'   => now()->year,
        ]))
            ->assertOk()
            ->assertSee($drilldownUrl, false);
    }
}
