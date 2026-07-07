<?php

namespace Tests\Feature\WorkloadReport;

use App\Jobs\GenerateWorkloadReportJob;
use App\Models\User;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class GenerateBatchAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_batch_generate_for_other_department(): void
    {

        Bus::fake();

        $leader = User::factory()->leader()->department('sales')->create();
        $salesStaff = User::factory()->staff()->department('sales')->create();
        $financeStaff = User::factory()->staff()->department('finance')->create();

        $this->actingAs($leader)
            ->postJson(route('workload-report.generateBatch'), [
                'month' => (int) now()->month,
                'year' => (int) now()->year,
                'department' => 'finance',
            ])
            ->assertOk();

        Bus::assertBatched(function (PendingBatch $batch) use ($salesStaff, $financeStaff) {
            $ids = collect($batch->jobs)->map(fn (GenerateWorkloadReportJob $j) => $j->staffId);

            return $ids->contains($financeStaff->id) && ! $ids->contains($salesStaff->id);
        });
    }

    public function test_staff_cannot_access_batch_generate(): void
    {
        $staff = User::factory()->staff()->department('sales')->create();

        $this->actingAs($staff)
            ->postJson(route('workload-report.generateBatch'), [
                'month' => (int) now()->month,
                'year' => (int) now()->year,
                'department' => 'sales',
            ])
            ->assertForbidden();
    }
}
