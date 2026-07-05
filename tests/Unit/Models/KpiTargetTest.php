<?php

namespace Tests\Unit\Models;

use App\Models\KpiActual;
use App\Models\KpiTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiTargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_aggregation_classification_helpers(): void
    {
        $this->assertTrue(KpiTarget::factory()->make(['aggregation' => 'sum'])->hasStaffBreakdown());
        $this->assertTrue(KpiTarget::factory()->make(['aggregation' => 'average'])->hasStaffBreakdown());
        $this->assertFalse(KpiTarget::factory()->make(['aggregation' => 'shared'])->hasStaffBreakdown());
        $this->assertFalse(KpiTarget::factory()->make(['aggregation' => 'milestone'])->hasStaffBreakdown());

        $this->assertTrue(KpiTarget::factory()->make(['aggregation' => 'shared'])->isDeptLevel());
        $this->assertTrue(KpiTarget::factory()->make(['aggregation' => 'milestone'])->isDeptLevel());
        $this->assertFalse(KpiTarget::factory()->make(['aggregation' => 'sum'])->isDeptLevel());

        $this->assertTrue(KpiTarget::factory()->make(['aggregation' => 'milestone'])->isMilestone());
        $this->assertFalse(KpiTarget::factory()->make(['aggregation' => 'sum'])->isMilestone());
    }

    public function test_sum_rollup_totals_child_actuals_over_allocated(): void
    {
        $exec  = User::factory()->cLevel()->create();
        $s1    = User::factory()->staff()->create();
        $s2    = User::factory()->staff()->create();
        $l2    = KpiTarget::factory()->level2()->aggregation('sum')->create(['target_value' => 10, 'set_by' => $exec->id]);
        $c1    = KpiTarget::factory()->level3($l2, $s1)->create(['target_value' => 4, 'set_by' => $exec->id]);
        $c2    = KpiTarget::factory()->level3($l2, $s2)->create(['target_value' => 6, 'set_by' => $exec->id]);

        KpiActual::factory()->forKpi($c1, $s1)->create(['actual_value' => 2]);
        KpiActual::factory()->forKpi($c2, $s2)->create(['actual_value' => 3]);

        $rollup = $l2->fresh()->deptRollup();

        $this->assertSame('sum', $rollup['aggregation']);
        $this->assertEqualsWithDelta(10.0, $rollup['allocated'], 0.01);
        $this->assertEqualsWithDelta(5.0, $rollup['actual'], 0.01);
        $this->assertSame(50, $rollup['pct']);
        $this->assertFalse($rollup['is_percent']);
    }

    public function test_average_rollup_averages_child_achievement_percent(): void
    {
        $exec = User::factory()->cLevel()->create();
        $s1   = User::factory()->staff()->create();
        $s2   = User::factory()->staff()->create();
        $l2   = KpiTarget::factory()->level2()->aggregation('average')->create(['target_value' => 100, 'set_by' => $exec->id]);
        $c1   = KpiTarget::factory()->level3($l2, $s1)->create(['target_value' => 4, 'set_by' => $exec->id]);
        $c2   = KpiTarget::factory()->level3($l2, $s2)->create(['target_value' => 6, 'set_by' => $exec->id]);

        KpiActual::factory()->forKpi($c1, $s1)->create(['actual_value' => 2]);
        KpiActual::factory()->forKpi($c2, $s2)->create(['actual_value' => 6]);

        $rollup = $l2->fresh()->deptRollup();

        $this->assertSame('average', $rollup['aggregation']);
        $this->assertTrue($rollup['is_percent']);
        $this->assertSame(75, $rollup['pct']);
    }

    public function test_shared_rollup_uses_dept_level_actual(): void
    {
        $exec = User::factory()->cLevel()->create();
        $l2   = KpiTarget::factory()->level2()->shared()->create(['target_value' => 20, 'set_by' => $exec->id]);

        KpiActual::factory()->create([
            'kpi_target_id' => $l2->id,
            'staff_id'      => null,
            'department'    => $l2->department,
            'month'         => $l2->month,
            'year'          => $l2->year,
            'actual_value'  => 15,
            'created_by'    => $exec->id,
        ]);

        $rollup = $l2->fresh()->deptRollup();

        $this->assertSame('shared', $rollup['aggregation']);
        $this->assertEqualsWithDelta(15.0, $rollup['actual'], 0.01);
        $this->assertSame(75, $rollup['pct']);
        $this->assertFalse($rollup['is_percent']);
    }

    public function test_milestone_rollup_reports_progress_percent(): void
    {
        $exec = User::factory()->cLevel()->create();
        $l2   = KpiTarget::factory()->level2()->milestone()->create(['set_by' => $exec->id]);

        KpiActual::factory()->create([
            'kpi_target_id' => $l2->id,
            'staff_id'      => null,
            'department'    => $l2->department,
            'month'         => $l2->month,
            'year'          => $l2->year,
            'actual_value'  => 40,
            'created_by'    => $exec->id,
        ]);

        $rollup = $l2->fresh()->deptRollup();

        $this->assertSame('milestone', $rollup['aggregation']);
        $this->assertTrue($rollup['is_percent']);
        $this->assertNull($rollup['target']);
        $this->assertSame(40, $rollup['pct']);
    }

    public function test_rollup_without_actuals_has_no_data(): void
    {
        $exec = User::factory()->cLevel()->create();
        $l2   = KpiTarget::factory()->level2()->aggregation('sum')->create(['target_value' => 10, 'set_by' => $exec->id]);

        $rollup = $l2->fresh()->deptRollup();

        $this->assertFalse($rollup['has_data']);
        $this->assertNull($rollup['pct']);
    }
}
