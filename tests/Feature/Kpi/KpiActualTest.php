<?php

namespace Tests\Feature\Kpi;

use App\Models\KpiActual;
use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiActualTest extends TestCase
{
    use RefreshDatabase;

    private function makeL3Setup(): array
    {
        $exec   = User::factory()->cLevel()->create();
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        $parent = KpiTarget::factory()->level2()->forDepartment('product_it')->create(['set_by' => $exec->id]);
        $l3     = KpiTarget::factory()->level3($parent, $staff)->create(['set_by' => $exec->id]);

        return compact('exec', 'staff', 'parent', 'l3');
    }

    public function test_c_level_can_store_kpi_actual(): void
    {
        ['exec' => $exec, 'staff' => $staff, 'l3' => $l3] = $this->makeL3Setup();

        $this->actingAs($exec)->post(route('kpi.actuals.store'), [
            'kpi_target_id' => $l3->id,
            'staff_id'      => $staff->id,
            'month'         => 6,
            'year'          => 2026,
            'actual_value'  => 8,
        ])->assertRedirect();

        $this->assertDatabaseHas('kpi_actuals', [
            'kpi_target_id' => $l3->id,
            'staff_id'      => $staff->id,
            'actual_value'  => 8,
        ]);
    }

    public function test_store_kpi_actual_is_idempotent_upsert(): void
    {
        ['exec' => $exec, 'staff' => $staff, 'l3' => $l3] = $this->makeL3Setup();

        $payload = [
            'kpi_target_id' => $l3->id,
            'staff_id'      => $staff->id,
            'month'         => 6,
            'year'          => 2026,
            'actual_value'  => 5,
        ];

        $this->actingAs($exec)->post(route('kpi.actuals.store'), $payload);
        $this->actingAs($exec)->post(route('kpi.actuals.store'), array_merge($payload, ['actual_value' => 9]));

        $this->assertSame(1, KpiActual::where('kpi_target_id', $l3->id)->where('staff_id', $staff->id)->count());
        $this->assertDatabaseHas('kpi_actuals', ['kpi_target_id' => $l3->id, 'actual_value' => 9]);
    }

    public function test_store_kpi_actual_requires_all_fields(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->post(route('kpi.actuals.store'), [])
            ->assertSessionHasErrors(['kpi_target_id', 'month', 'year', 'actual_value'])
            ->assertSessionDoesntHaveErrors(['staff_id']);
    }

    public function test_leader_without_management_cannot_store_kpi_actual(): void
    {
        ['staff' => $staff, 'l3' => $l3] = $this->makeL3Setup();
        $leader = User::factory()->leader()->create(['is_management' => false]);

        $this->actingAs($leader)->post(route('kpi.actuals.store'), [
            'kpi_target_id' => $l3->id,
            'staff_id'      => $staff->id,
            'month'         => 6,
            'year'          => 2026,
            'actual_value'  => 7,
        ])->assertForbidden();
    }

    public function test_c_level_can_update_kpi_actual(): void
    {
        ['exec' => $exec, 'staff' => $staff, 'l3' => $l3] = $this->makeL3Setup();

        $actual = KpiActual::factory()->create([
            'kpi_target_id' => $l3->id,
            'staff_id'      => $staff->id,
            'department'    => 'product_it',
            'actual_value'  => 3,
            'month'         => 6,
            'year'          => 2026,
            'source'        => 'manual',
            'created_by'    => $exec->id,
        ]);

        $this->actingAs($exec)->patch(route('kpi.actuals.update', $actual), [
            'actual_value' => 10,
        ])->assertRedirect();

        $this->assertDatabaseHas('kpi_actuals', ['id' => $actual->id, 'actual_value' => 10]);
    }
}
