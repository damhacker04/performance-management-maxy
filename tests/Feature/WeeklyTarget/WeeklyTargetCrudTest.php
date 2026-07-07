<?php

namespace Tests\Feature\WeeklyTarget;

use App\Models\MonthlyTarget;
use App\Models\User;
use App\Models\WeeklyTarget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyTargetCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_view_create_form(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);

        $this->actingAs($leader)
            ->get(route('weekly-targets.create', ['monthly_target_id' => $mt->id]))
            ->assertOk();
    }

    public function test_staff_cannot_access_weekly_target_create(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->get(route('weekly-targets.create'))->assertForbidden();
    }

    public function test_leader_can_store_weekly_target_for_own_department(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);

        $this->actingAs($leader)->post(route('weekly-targets.store'), [
            'monthly_target_id' => $mt->id,
            'title'             => 'Target Mingguan Baru',
            'week_number'       => 1,
            'target_type'       => 'qualitative',
            'assigned_to'       => $staff->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('weekly_targets', [
            'monthly_target_id' => $mt->id,
            'title'             => 'Target Mingguan Baru',
            'assigned_to'       => $staff->id,
        ]);
    }

    public function test_store_redirects_to_back_url_from_post_body(): void
    {
        $leader  = User::factory()->leader()->create(['department' => 'product_it']);
        $staff   = User::factory()->staff()->create(['department' => 'product_it']);
        $mt      = MonthlyTarget::factory()->assignedTo($staff)->period(6, 2026)->create([
            'user_id'    => $leader->id,
            'department' => 'product_it',
        ]);

        $backUrl = route('period.staff-weekly', [
            'year'          => 2026,
            'month'         => 6,
            'staff'         => $staff->id,
            'monthlyTarget' => $mt->id,
        ]);

        $this->actingAs($leader)->post(route('weekly-targets.store'), [
            'monthly_target_id' => $mt->id,
            'title'             => 'Target Dengan Back',
            'week_number'       => 1,
            'target_type'       => 'qualitative',
            'back'              => $backUrl,
        ])->assertRedirect($backUrl);
    }

    public function test_store_falls_back_to_monthly_target_show_when_no_back(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);

        $this->actingAs($leader)->post(route('weekly-targets.store'), [
            'monthly_target_id' => $mt->id,
            'title'             => 'Target Tanpa Back',
            'week_number'       => 2,
            'target_type'       => 'qualitative',
        ])->assertRedirect(route('period.staff-list', ['year' => $mt->year, 'month' => $mt->month]));
    }

    public function test_store_requires_title_and_week_number(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);

        $this->actingAs($leader)->post(route('weekly-targets.store'), [
            'monthly_target_id' => $mt->id,
            'target_type'       => 'qualitative',
        ])->assertSessionHasErrors(['title', 'week_number']);
    }

    public function test_quantitative_target_requires_value_and_unit(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);

        $this->actingAs($leader)->post(route('weekly-targets.store'), [
            'monthly_target_id' => $mt->id,
            'title'             => 'Target Kuantitatif',
            'week_number'       => 1,
            'target_type'       => 'quantitative',

        ])->assertSessionHasErrors(['target_value', 'target_unit']);
    }

    public function test_leader_cannot_store_weekly_target_for_other_department(): void
    {
        $leader    = User::factory()->leader()->create(['department' => 'product_it']);
        $otherLeader = User::factory()->leader()->create(['department' => 'sales']);
        $mt        = MonthlyTarget::factory()->create(['department' => 'sales', 'user_id' => $otherLeader->id]);

        $this->actingAs($leader)->post(route('weekly-targets.store'), [
            'monthly_target_id' => $mt->id,
            'title'             => 'Hacked',
            'week_number'       => 1,
            'target_type'       => 'qualitative',
        ])->assertForbidden();
    }

    public function test_leader_can_update_weekly_target(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);
        $wt     = WeeklyTarget::factory()->forMonthly($mt)->create();

        $this->actingAs($leader)->patch(route('weekly-targets.update', $wt), [
            'monthly_target_id' => $mt->id,
            'title'             => 'Judul Diupdate',
            'week_number'       => 2,
            'target_type'       => 'qualitative',
        ])->assertRedirect();

        $this->assertDatabaseHas('weekly_targets', ['id' => $wt->id, 'title' => 'Judul Diupdate']);
    }

    public function test_update_redirects_to_back_url_from_post_body(): void
    {
        $leader  = User::factory()->leader()->create(['department' => 'product_it']);
        $staff   = User::factory()->staff()->create(['department' => 'product_it']);
        $mt      = MonthlyTarget::factory()->assignedTo($staff)->period(6, 2026)->create([
            'user_id'    => $leader->id,
            'department' => 'product_it',
        ]);
        $wt = WeeklyTarget::factory()->forMonthly($mt)->create();

        $backUrl = route('period.staff-weekly', [
            'year'          => 2026,
            'month'         => 6,
            'staff'         => $staff->id,
            'monthlyTarget' => $mt->id,
        ]);

        $this->actingAs($leader)->patch(route('weekly-targets.update', $wt), [
            'monthly_target_id' => $mt->id,
            'title'             => 'Update Dengan Back',
            'week_number'       => 1,
            'target_type'       => 'qualitative',
            'back'              => $backUrl,
        ])->assertRedirect($backUrl);
    }

    public function test_leader_can_destroy_own_weekly_target(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);
        $wt     = WeeklyTarget::factory()->forMonthly($mt)->create();

        $this->actingAs($leader)->delete(route('weekly-targets.destroy', $wt))
            ->assertRedirect();

        $this->assertDatabaseMissing('weekly_targets', ['id' => $wt->id]);
    }

    public function test_leader_cannot_destroy_other_department_weekly_target(): void
    {
        $leader    = User::factory()->leader()->create(['department' => 'product_it']);
        $otherLeader = User::factory()->leader()->create(['department' => 'sales']);
        $mt        = MonthlyTarget::factory()->create(['department' => 'sales', 'user_id' => $otherLeader->id]);
        $wt        = WeeklyTarget::factory()->forMonthly($mt)->create();

        $this->actingAs($leader)->delete(route('weekly-targets.destroy', $wt))
            ->assertForbidden();

        $this->assertDatabaseHas('weekly_targets', ['id' => $wt->id]);
    }

    public function test_period_weekly_show_accessible_by_leader(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->assignedTo($staff)->period(6, 2026)->create([
            'user_id'    => $leader->id,
            'department' => 'product_it',
        ]);
        $wt = WeeklyTarget::factory()->forMonthly($mt)->assignedTo($staff)->create();

        $this->actingAs($leader)
            ->get(route('period.weekly-show', [
                'year'          => 2026,
                'month'         => 6,
                'staff'         => $staff->id,
                'monthlyTarget' => $mt->id,
                'weeklyTarget'  => $wt->id,
            ]))
            ->assertOk();
    }
}
