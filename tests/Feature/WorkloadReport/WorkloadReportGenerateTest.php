<?php

namespace Tests\Feature\WorkloadReport;

use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class WorkloadReportGenerateTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGemini(): void
    {
        $this->mock(GeminiService::class, function (Mockery\MockInterface $mock) {
            $mock->shouldReceive('generateWorkloadReport')->andReturn([
                'score'              => 85,
                'summary_flag'       => 'baik',
                'achievement'        => 'Contoh ringkasan.',
                'optimization_areas' => [],
                'recommendations'    => [],
            ]);
        });
    }

    public function test_leader_can_view_workload_report_index(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $this->actingAs($leader)->get(route('workload-report.index'))->assertOk();
    }

    public function test_c_level_can_view_workload_report_index(): void
    {
        $exec = User::factory()->cLevel()->create();
        $this->actingAs($exec)->get(route('workload-report.index'))->assertOk();
    }

    public function test_staff_cannot_access_workload_report(): void
    {
        $staff = User::factory()->staff()->create(['is_management' => false]);
        $this->actingAs($staff)->get(route('workload-report.index'))->assertForbidden();
    }

    public function test_leader_index_lists_staff_from_all_departments(): void
    {

        $leader     = User::factory()->leader()->create(['department' => 'product_it']);
        $ownStaff   = User::factory()->staff()->create(['department' => 'product_it', 'name' => 'Staf Produk']);
        $otherStaff = User::factory()->staff()->create(['department' => 'sales', 'name' => 'Staf Sales']);

        $this->actingAs($leader)->get(route('workload-report.index'))
            ->assertOk()
            ->assertSee('Staf Produk')
            ->assertSee('Staf Sales');
    }

    public function test_leader_can_view_workload_report_for_own_dept_staff(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);

        $this->actingAs($leader)
            ->get(route('workload-report.show', [$staff->id, now()->month, now()->year]))
            ->assertOk();
    }

    public function test_leader_can_view_workload_report_for_other_dept_staff(): void
    {

        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'sales']);

        $this->actingAs($leader)
            ->get(route('workload-report.show', [$staff->id, now()->month, now()->year]))
            ->assertOk();
    }

    public function test_leader_can_trigger_single_report_generation(): void
    {
        $this->fakeGemini();

        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);

        $this->actingAs($leader)->post(route('workload-report.generate'), [
            'staff_id' => $staff->id,
            'month'    => now()->month,
            'year'     => now()->year,
        ])->assertSuccessful();
    }

    public function test_leader_can_trigger_single_report_generation_for_other_dept(): void
    {
        $this->fakeGemini();

        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'sales']);

        $this->actingAs($leader)->post(route('workload-report.generate'), [
            'staff_id' => $staff->id,
            'month'    => now()->month,
            'year'     => now()->year,
        ])->assertSuccessful();
    }

    public function test_staff_cannot_trigger_single_report_generation(): void
    {
        $staff = User::factory()->staff()->create(['is_management' => false]);
        $other = User::factory()->staff()->create();

        $this->actingAs($staff)->post(route('workload-report.generate'), [
            'staff_id' => $other->id,
            'month'    => now()->month,
            'year'     => now()->year,
        ])->assertForbidden();
    }

    public function test_leader_can_batch_generate_own_department(): void
    {
        Bus::fake();

        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        User::factory()->staff()->create(['department' => 'product_it']);

        $this->actingAs($leader)->post(route('workload-report.generateBatch'), [
            'department' => 'product_it',
            'month'      => now()->month,
            'year'       => now()->year,
        ])->assertSuccessful();
    }
}
