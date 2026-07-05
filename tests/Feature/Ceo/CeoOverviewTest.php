<?php

namespace Tests\Feature\Ceo;

use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CeoOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_c_level_can_view_overview(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->get(route('ceo.overview'))->assertOk();
    }

    public function test_staff_cannot_view_overview(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('ceo.overview'))->assertForbidden();
    }

    public function test_leader_cannot_view_overview(): void
    {
        $leader = User::factory()->leader()->create();

        $this->actingAs($leader)->get(route('ceo.overview'))->assertForbidden();
    }

    public function test_monthly_target_index_redirects_c_level_to_overview(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->get(route('monthly-targets.index'))
            ->assertRedirect(route('ceo.overview'));
    }

    public function test_c_level_navigation_has_overview_link(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->get(route('ceo.overview'))
            ->assertOk()
            ->assertSee(route('ceo.overview'), false);
    }

    public function test_staff_navigation_has_no_overview_link(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('ceo.overview'), false);
    }

    public function test_overview_lists_low_progress_staff_as_needing_attention(): void
    {
        $exec  = User::factory()->cLevel()->create();
        $staff = User::factory()->staff()->create(['department' => 'creative', 'name' => 'Staf Lambat']);

        DailyTaskEntry::factory()->forUser($staff)->status('selesai')->create(['task_date' => now()->toDateString()]);
        DailyTaskEntry::factory()->forUser($staff)->status('dalam_proses')->create(['task_date' => now()->toDateString()]);
        DailyTaskEntry::factory()->forUser($staff)->status('dalam_proses')->create(['task_date' => now()->toDateString()]);

        $this->actingAs($exec)->get(route('ceo.overview'))
            ->assertOk()
            ->assertSee('Staf Lambat');
    }
}
