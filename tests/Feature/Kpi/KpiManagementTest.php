<?php

namespace Tests\Feature\Kpi;

use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_c_level_can_store_kpi_target(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->post(route('kpi.store'), [
            'department'   => 'product_it',
            'aggregation'  => 'sum',
            'kpi_name'     => 'Fitur Dirilis',
            'target_value' => 10,
            'unit'         => 'fitur',
            'month'        => 6,
            'year'         => 2026,
        ])->assertRedirect();

        $this->assertDatabaseHas('kpi_targets', [
            'kpi_name'    => 'Fitur Dirilis',
            'department'  => 'product_it',
            'kpi_level'   => 2,
            'aggregation' => 'sum',
        ]);
    }

    public function test_kpi_store_requires_mandatory_fields(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->post(route('kpi.store'), [])
            ->assertSessionHasErrors(['department', 'aggregation', 'kpi_name', 'target_value', 'unit', 'month', 'year']);
    }

    public function test_leader_cannot_store_kpi(): void
    {
        $leader = User::factory()->leader()->create(['is_management' => false]);

        $this->actingAs($leader)->post(route('kpi.store'), [
            'department'   => 'product_it',
            'aggregation'  => 'sum',
            'kpi_name'     => 'Hacked KPI',
            'target_value' => 5,
            'unit'         => 'unit',
            'month'        => 6,
            'year'         => 2026,
        ])->assertForbidden();
    }

    public function test_c_level_can_update_kpi(): void
    {
        $exec = User::factory()->cLevel()->create();
        $kpi  = KpiTarget::factory()->level2()->create(['set_by' => $exec->id]);

        $this->actingAs($exec)->put(route('kpi.update', $kpi), [
            'department'   => $kpi->department,
            'aggregation'  => $kpi->aggregation,
            'kpi_name'     => 'KPI Diupdate',
            'target_value' => 20,
            'unit'         => 'unit',
            'month'        => $kpi->month,
            'year'         => $kpi->year,
        ])->assertRedirect();

        $this->assertDatabaseHas('kpi_targets', ['id' => $kpi->id, 'kpi_name' => 'KPI Diupdate']);
    }

    public function test_c_level_can_destroy_kpi(): void
    {
        $exec = User::factory()->cLevel()->create();
        $kpi  = KpiTarget::factory()->level2()->create(['set_by' => $exec->id]);

        $this->actingAs($exec)->delete(route('kpi.destroy', $kpi))
            ->assertRedirect();

        $this->assertDatabaseHas('kpi_targets', ['id' => $kpi->id, 'is_active' => false]);
    }

    public function test_c_level_can_view_create_staff_kpi_form(): void
    {
        $exec = User::factory()->cLevel()->create();
        $this->actingAs($exec)->get(route('kpi.staff.create'))->assertOk();
    }

    public function test_c_level_can_store_staff_kpi(): void
    {
        $exec   = User::factory()->cLevel()->create();
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        $parent = KpiTarget::factory()->level2()->forDepartment('product_it')->create(['set_by' => $exec->id]);

        $this->actingAs($exec)->post(route('kpi.staff.store'), [
            'parent_id'    => $parent->id,
            'user_id'      => $staff->id,
            'target_value' => 5,
            'notes'        => 'Target individual staf',
            'month'        => 6,
            'year'         => 2026,
        ])->assertRedirect();

        $this->assertDatabaseHas('kpi_targets', [
            'parent_id'  => $parent->id,
            'user_id'    => $staff->id,
            'kpi_level'  => 3,
        ]);
    }

    public function test_staff_kpi_store_requires_parent_and_user(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->post(route('kpi.staff.store'), [
            'target_value' => 5,
            'month'        => 6,
            'year'         => 2026,
        ])->assertSessionHasErrors(['parent_id', 'user_id']);
    }

    public function test_c_level_can_view_kpi_actuals(): void
    {
        $exec = User::factory()->cLevel()->create();
        $this->actingAs($exec)->get(route('kpi.actuals.index'))->assertOk();
    }

    public function test_leader_cannot_view_kpi_actuals(): void
    {
        $leader = User::factory()->leader()->create(['is_management' => false]);
        $this->actingAs($leader)->get(route('kpi.actuals.index'))->assertForbidden();
    }
}
