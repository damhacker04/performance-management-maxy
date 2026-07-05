<?php

namespace Tests\Feature\Seeder;

use App\Models\AppNotification;
use App\Models\DailyTaskEntry;
use App\Models\MonthlyTarget;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UseCaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_seeder_runs_without_error(): void
    {
        $this->seed(DatabaseSeeder::class);

        foreach ([
            'adminhr.maxy.academy@gmail.com',
            'isaac.maxy.academy@gmail.com',
            'leader.operational@maxy.academy',
            'staff.testing@maxy.academy',
        ] as $email) {
            $this->assertDatabaseHas('users', ['email' => $email]);
        }
    }

    public function test_testing_staff_has_targets_and_tasks_of_their_own(): void
    {
        $this->seed(DatabaseSeeder::class);

        $staff = User::where('email', 'staff.testing@maxy.academy')->firstOrFail();

        $this->assertTrue(
            MonthlyTarget::where('assigned_to', $staff->id)->exists(),
            'staff.testing harus punya monthly target ter-assign.'
        );
        $this->assertTrue(
            DailyTaskEntry::where('user_id', $staff->id)->exists(),
            'staff.testing harus punya laporan harian.'
        );
    }

    public function test_testing_leader_can_review_seeded_team_reports(): void
    {
        $this->seed(DatabaseSeeder::class);

        $leader = User::where('email', 'leader.operational@maxy.academy')->firstOrFail();

        $pending = DailyTaskEntry::whereIn('verification_status', ['pending', 'revision'])
            ->whereHas('user', fn ($q) => $q->where('department', $leader->department)->where('role', 'staff'))
            ->exists();
        $this->assertTrue($pending, 'Leader operational harus punya laporan tim untuk direview.');
    }

    public function test_testing_staff_has_linked_rejected_report_notification(): void
    {
        $this->seed(DatabaseSeeder::class);

        $staff = User::where('email', 'staff.testing@maxy.academy')->firstOrFail();

        $notif = AppNotification::where('user_id', $staff->id)
            ->where('type', AppNotification::TYPE_REPORT_REJECTED)
            ->whereNull('read_at')
            ->firstOrFail();

        $this->assertDatabaseHas('daily_task_entries', [
            'id'                  => $notif->related_id,
            'user_id'             => $staff->id,
            'verification_status' => 'rejected',
        ]);
    }

    public function test_seeder_creates_dept_level_kpis_with_null_staff_actuals(): void
    {
        $this->seed(DatabaseSeeder::class);

        $deptKpi = \App\Models\KpiTarget::whereIn('aggregation', ['shared', 'milestone'])
            ->where('kpi_level', 2)
            ->firstOrFail();

        $this->assertDatabaseHas('kpi_actuals', [
            'kpi_target_id' => $deptKpi->id,
            'staff_id'      => null,
        ]);
    }

    public function test_testing_c_level_sees_org_wide_data_on_overview(): void
    {
        $this->seed(DatabaseSeeder::class);

        $exec = User::where('email', 'isaac.maxy.academy@gmail.com')->firstOrFail();

        $this->actingAs($exec)->get(route('ceo.overview'))
            ->assertOk()
            ->assertSee('Progress Staf');
    }
}
