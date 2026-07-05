<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_view_kpi_list_but_not_manage(): void
    {
        $leader = User::factory()->create([
            'role' => 'leader', 'department' => 'operational', 'is_management' => false,
        ]);

        $this->actingAs($leader)->get(route('kpi'))->assertOk();
        $this->actingAs($leader)->get(route('kpi.create'))->assertForbidden();
    }

    public function test_c_level_can_manage_kpi(): void
    {
        $exec = User::factory()->create(['role' => 'c_level', 'is_management' => true]);

        $this->actingAs($exec)->get(route('kpi.create'))->assertOk();
    }

    public function test_management_leader_can_manage_kpi(): void
    {

        $mgmt = User::factory()->create([
            'role' => 'leader', 'department' => 'operational', 'is_management' => true,
        ]);

        $this->actingAs($mgmt)->get(route('kpi.create'))->assertOk();
    }

    public function test_staff_cannot_reach_kpi_section_at_all(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'is_management' => false]);

        $this->actingAs($staff)->get(route('kpi'))->assertForbidden();
    }
}
