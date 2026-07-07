<?php

namespace Tests\Feature\MonthlyTarget;

use App\Models\MonthlyTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RemapClevelTargetsTest extends TestCase
{
    use RefreshDatabase;

    private function runRemap(): void
    {
        $migration = require base_path(
            'database/migrations/2026_06_25_102636_remap_clevel_staff_targets_to_leader.php'
        );
        $migration->up();
    }

    public function test_legacy_clevel_staff_target_is_remapped_to_department_leader(): void
    {

        $exec   = User::factory()->cLevel()->create();
        $leader = User::factory()->leader()->create(['department' => 'creative']);
        $staff  = User::factory()->staff()->create(['department' => 'creative']);

        $mt = MonthlyTarget::factory()->create([
            'user_id'     => $exec->id,
            'assigned_to' => $staff->id,
            'department'  => 'creative',
        ]);

        $this->runRemap();

        $this->assertSame($leader->id, $mt->fresh()->assigned_to);
    }

    public function test_target_in_department_without_leader_becomes_unassigned(): void
    {
        $exec  = User::factory()->cLevel()->create();
        $staff = User::factory()->staff()->create(['department' => 'finance']);

        $mt = MonthlyTarget::factory()->create([
            'user_id'     => $exec->id,
            'assigned_to' => $staff->id,
            'department'  => 'finance',
        ]);

        $this->runRemap();

        $this->assertNull($mt->fresh()->assigned_to);
    }

    public function test_leader_created_targets_are_untouched(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);

        $mt = MonthlyTarget::factory()->create([
            'user_id'     => $leader->id,
            'assigned_to' => $staff->id,
            'department'  => 'product_it',
        ]);

        $this->runRemap();

        $this->assertSame($staff->id, $mt->fresh()->assigned_to);
    }
}
