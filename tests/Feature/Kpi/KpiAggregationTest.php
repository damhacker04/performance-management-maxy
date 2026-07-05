<?php

namespace Tests\Feature\Kpi;

use App\Models\KpiActual;
use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_milestone_kpi_forces_progress_convention(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->post(route('kpi.store'), [
            'department'   => 'operational',
            'aggregation'  => 'milestone',
            'kpi_name'     => 'Peluncuran Sistem Baru',
            'month'        => 6,
            'year'         => 2026,
        ])->assertRedirect();

        $this->assertDatabaseHas('kpi_targets', [
            'kpi_name'     => 'Peluncuran Sistem Baru',
            'aggregation'  => 'milestone',
            'target_value' => 100,
            'unit'         => '%',
        ]);
    }

    public function test_store_shared_kpi_persists_without_staff_breakdown(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->post(route('kpi.store'), [
            'department'   => 'sales',
            'aggregation'  => 'shared',
            'kpi_name'     => 'Revenue Tim',
            'target_value' => 500,
            'unit'         => 'juta',
            'month'        => 6,
            'year'         => 2026,
        ])->assertRedirect();

        $this->assertDatabaseHas('kpi_targets', [
            'kpi_name'    => 'Revenue Tim',
            'aggregation' => 'shared',
            'kpi_level'   => 2,
        ]);
    }

    public function test_store_actual_for_dept_level_kpi_ignores_staff_id(): void
    {
        $exec   = User::factory()->cLevel()->create();
        $staff  = User::factory()->staff()->create(['department' => 'operational']);
        $shared = KpiTarget::factory()->level2()->shared()->forDepartment('operational')->create([
            'target_value' => 20,
            'set_by'       => $exec->id,
        ]);

        $this->actingAs($exec)->post(route('kpi.actuals.store'), [
            'kpi_target_id' => $shared->id,
            'staff_id'      => $staff->id,
            'month'         => 6,
            'year'          => 2026,
            'actual_value'  => 12,
        ])->assertRedirect();

        $this->assertDatabaseHas('kpi_actuals', [
            'kpi_target_id' => $shared->id,
            'staff_id'      => null,
            'actual_value'  => 12,
        ]);
    }

    public function test_store_actual_for_milestone_clamps_to_hundred(): void
    {
        $exec      = User::factory()->cLevel()->create();
        $milestone = KpiTarget::factory()->level2()->milestone()->forDepartment('operational')->create([
            'set_by' => $exec->id,
        ]);

        $this->actingAs($exec)->post(route('kpi.actuals.store'), [
            'kpi_target_id' => $milestone->id,
            'month'         => 6,
            'year'          => 2026,
            'actual_value'  => 250,
        ])->assertRedirect();

        $actual = KpiActual::where('kpi_target_id', $milestone->id)->firstOrFail();
        $this->assertSame(100.0, (float) $actual->actual_value);
        $this->assertNull($actual->staff_id);
    }

    public function test_update_kpi_cannot_change_aggregation_type(): void
    {
        $exec = User::factory()->cLevel()->create();
        $kpi  = KpiTarget::factory()->level2()->aggregation('sum')->create(['set_by' => $exec->id]);

        $this->actingAs($exec)->put(route('kpi.update', $kpi), [
            'department'   => $kpi->department,
            'aggregation'  => 'milestone',
            'kpi_name'     => 'Coba Ubah Jenis',
            'target_value' => 30,
            'unit'         => 'unit',
            'month'        => $kpi->month,
            'year'         => $kpi->year,
        ])->assertRedirect();

        $this->assertDatabaseHas('kpi_targets', [
            'id'          => $kpi->id,
            'kpi_name'    => 'Coba Ubah Jenis',
            'aggregation' => 'sum',
        ]);
    }

    public function test_staff_kpi_only_available_for_breakdown_parents(): void
    {
        $exec = User::factory()->cLevel()->create();
        KpiTarget::factory()->level2()->aggregation('sum')->forDepartment('operational')->create([
            'kpi_name' => 'KPI Sum Terpecah',
            'set_by'   => $exec->id,
        ]);
        KpiTarget::factory()->level2()->shared()->forDepartment('operational')->create([
            'kpi_name' => 'KPI Shared Tim',
            'set_by'   => $exec->id,
        ]);

        $this->actingAs($exec)->get(route('kpi.staff.create'))
            ->assertOk()
            ->assertSee('KPI Sum Terpecah')
            ->assertDontSee('KPI Shared Tim');
    }
}
