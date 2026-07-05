<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_kpi_settings(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $this->actingAs($admin)->get(route('admin.kpi-settings.index'))->assertOk();
    }

    public function test_c_level_cannot_access_kpi_settings(): void
    {
        $exec = User::factory()->cLevel()->create();
        $this->actingAs($exec)->get(route('admin.kpi-settings.index'))->assertForbidden();
    }

    public function test_super_admin_can_store_kpi_weight_settings(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.kpi-settings.store'), [
            'weight_achievement'     => 30,
            'weight_efficiency'      => 30,
            'weight_contribution'    => 20,
            'weight_problem_solving' => 20,
            'effective_from'         => '2026-07-01',
        ])->assertRedirect(route('admin.kpi-settings.index'));

        $this->assertDatabaseHas('kpi_weight_settings', [
            'weight_achievement'     => 30,
            'weight_efficiency'      => 30,
            'weight_contribution'    => 20,
            'weight_problem_solving' => 20,
            'is_active'              => true,
        ]);
    }

    public function test_weights_must_sum_to_100(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.kpi-settings.store'), [
            'weight_achievement'     => 30,
            'weight_efficiency'      => 30,
            'weight_contribution'    => 30,
            'weight_problem_solving' => 30,
            'effective_from'         => '2026-07-01',
        ])->assertSessionHasErrors('total');
    }

    public function test_store_requires_all_weight_fields(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.kpi-settings.store'), [
            'effective_from' => '2026-07-01',
        ])->assertSessionHasErrors([
            'weight_achievement',
            'weight_efficiency',
            'weight_contribution',
            'weight_problem_solving',
        ]);
    }

    public function test_new_setting_deactivates_previous_one(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.kpi-settings.store'), [
            'weight_achievement'     => 25,
            'weight_efficiency'      => 25,
            'weight_contribution'    => 25,
            'weight_problem_solving' => 25,
            'effective_from'         => '2026-06-01',
        ]);

        $this->actingAs($admin)->post(route('admin.kpi-settings.store'), [
            'weight_achievement'     => 40,
            'weight_efficiency'      => 20,
            'weight_contribution'    => 20,
            'weight_problem_solving' => 20,
            'effective_from'         => '2026-07-01',
        ]);

        $this->assertSame(1, \App\Models\KpiWeightSetting::where('is_active', true)->count());
        $this->assertDatabaseHas('kpi_weight_settings', ['weight_achievement' => 40, 'is_active' => true]);
    }
}
