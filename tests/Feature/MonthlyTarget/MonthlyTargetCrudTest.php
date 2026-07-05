<?php

namespace Tests\Feature\MonthlyTarget;

use App\Models\MonthlyTarget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyTargetCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_can_view_monthly_target_index(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $this->actingAs($leader)->get(route('monthly-targets.index'))->assertOk();
    }

    public function test_staff_cannot_access_monthly_target_index(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->get(route('monthly-targets.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_from_monthly_target_index(): void
    {
        $this->get(route('monthly-targets.index'))->assertRedirect(route('login'));
    }

    public function test_leader_can_create_monthly_target_for_own_department(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);

        $this->actingAs($leader)->post(route('monthly-targets.store'), [
            'title'       => 'Target Baru',
            'description' => 'Deskripsi target',
            'month'       => 6,
            'year'        => 2026,
            'assigned_to' => $staff->id,
        ])->assertRedirect(route('monthly-targets.index'));

        $this->assertDatabaseHas('monthly_targets', [
            'title'       => 'Target Baru',
            'user_id'     => $leader->id,
            'assigned_to' => $staff->id,
            'department'  => 'product_it',
            'month'       => 6,
            'year'        => 2026,
        ]);
    }

    public function test_store_requires_title(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);

        $this->actingAs($leader)->post(route('monthly-targets.store'), [
            'month' => 6,
            'year'  => 2026,
        ])->assertSessionHasErrors('title');
    }

    public function test_c_level_must_provide_department_when_creating(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->post(route('monthly-targets.store'), [
            'title' => 'Target C-Level',
            'month' => 6,
            'year'  => 2026,
        ])->assertSessionHasErrors('department');
    }

    public function test_c_level_can_create_monthly_target_with_department(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->post(route('monthly-targets.store'), [
            'title'      => 'Target C-Level',
            'month'      => 6,
            'year'       => 2026,
            'department' => 'product_it',
        ])->assertRedirect(route('monthly-targets.index'));

        $this->assertDatabaseHas('monthly_targets', [
            'title'      => 'Target C-Level',
            'department' => 'product_it',
        ]);
    }

    public function test_c_level_cannot_assign_target_directly_to_staff(): void
    {
        $exec  = User::factory()->cLevel()->create();
        $staff = User::factory()->staff()->create(['department' => 'product_it']);

        $this->actingAs($exec)->post(route('monthly-targets.store'), [
            'title'       => 'Target langsung ke staff',
            'month'       => 6,
            'year'        => 2026,
            'department'  => 'product_it',
            'assigned_to' => $staff->id,
        ])->assertSessionHasErrors('assigned_to');

        $this->assertDatabaseMissing('monthly_targets', ['title' => 'Target langsung ke staff']);
    }

    public function test_c_level_can_assign_target_to_leader(): void
    {
        $exec   = User::factory()->cLevel()->create();
        $leader = User::factory()->leader()->create(['department' => 'product_it']);

        $this->actingAs($exec)->post(route('monthly-targets.store'), [
            'title'       => 'Arahan untuk leader',
            'month'       => 6,
            'year'        => 2026,
            'department'  => 'product_it',
            'assigned_to' => $leader->id,
        ])->assertRedirect(route('monthly-targets.index'));

        $this->assertDatabaseHas('monthly_targets', [
            'title'       => 'Arahan untuk leader',
            'user_id'     => $exec->id,
            'assigned_to' => $leader->id,
        ]);
    }

    public function test_leader_can_view_own_department_monthly_target_in_period(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id, 'assigned_to' => $staff->id]);

        $this->actingAs($leader)->get(route('period.staff-weekly', [
            'year'          => $mt->year,
            'month'         => $mt->month,
            'staff'         => $staff->id,
            'monthlyTarget' => $mt->id,
        ]))->assertOk();
    }

    public function test_leader_cannot_view_other_department_monthly_target_in_period(): void
    {
        $leader     = User::factory()->leader()->create(['department' => 'product_it']);
        $otherStaff = User::factory()->staff()->create(['department' => 'sales']);
        $other      = User::factory()->leader()->create(['department' => 'sales']);
        $mt         = MonthlyTarget::factory()->create(['department' => 'sales', 'user_id' => $other->id, 'assigned_to' => $otherStaff->id]);

        $this->actingAs($leader)->get(route('period.staff-weekly', [
            'year'          => $mt->year,
            'month'         => $mt->month,
            'staff'         => $otherStaff->id,
            'monthlyTarget' => $mt->id,
        ]))->assertForbidden();
    }

    public function test_show_staff_page_has_edit_link_for_leader(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->assignedTo($staff)->period(6, 2026)->create([
            'user_id'    => $leader->id,
            'department' => 'product_it',
            'title'      => 'Target Edit Link',
        ]);

        $this->actingAs($leader)->get(route('period.staff-weekly', [
            'year'          => 2026,
            'month'         => 6,
            'staff'         => $staff->id,
            'monthlyTarget' => $mt->id,
        ]))->assertOk()
            ->assertSee('Target Edit Link')
            ->assertSee(route('monthly-targets.edit', $mt), false);
    }

    public function test_leader_can_update_own_department_monthly_target(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);

        $this->actingAs($leader)->put(route('monthly-targets.update', $mt), [
            'title' => 'Judul Diupdate',
            'month' => $mt->month,
            'year'  => $mt->year,
        ])->assertRedirect(route('monthly-targets.index'));

        $this->assertDatabaseHas('monthly_targets', ['id' => $mt->id, 'title' => 'Judul Diupdate']);
    }

    public function test_leader_cannot_update_other_department_monthly_target(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $other  = User::factory()->leader()->create(['department' => 'sales']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'sales', 'user_id' => $other->id]);

        $this->actingAs($leader)->put(route('monthly-targets.update', $mt), [
            'title' => 'Hacked',
            'month' => $mt->month,
            'year'  => $mt->year,
        ])->assertForbidden();
    }

    public function test_leader_can_destroy_own_monthly_target(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->create(['department' => 'product_it', 'user_id' => $leader->id]);

        $this->actingAs($leader)->delete(route('monthly-targets.destroy', $mt))
            ->assertRedirect(route('monthly-targets.index'));

        $this->assertDatabaseMissing('monthly_targets', ['id' => $mt->id]);
    }

    public function test_period_staff_list_accessible_by_leader(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);

        $this->actingAs($leader)
            ->get(route('period.staff-list', ['year' => 2026, 'month' => 6]))
            ->assertOk();
    }

    public function test_period_staff_targets_shows_staff_monthly_targets(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        MonthlyTarget::factory()->assignedTo($staff)->period(6, 2026)->create([
            'user_id'    => $leader->id,
            'department' => 'product_it',
        ]);

        $this->actingAs($leader)
            ->get(route('period.staff-targets', ['year' => 2026, 'month' => 6, 'staff' => $staff->id]))
            ->assertOk();
    }

    public function test_period_staff_weekly_accessible_with_valid_context(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $staff  = User::factory()->staff()->create(['department' => 'product_it']);
        $mt     = MonthlyTarget::factory()->assignedTo($staff)->period(6, 2026)->create([
            'user_id'    => $leader->id,
            'department' => 'product_it',
        ]);

        $this->actingAs($leader)
            ->get(route('period.staff-weekly', [
                'year'          => 2026,
                'month'         => 6,
                'staff'         => $staff->id,
                'monthlyTarget' => $mt->id,
            ]))
            ->assertOk();
    }

    public function test_leader_cannot_access_other_department_in_period_hierarchy(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'product_it']);
        $other  = User::factory()->staff()->create(['department' => 'sales']);
        $mt     = MonthlyTarget::factory()->assignedTo($other)->period(6, 2026)->create([
            'department' => 'sales',
        ]);

        $this->actingAs($leader)
            ->get(route('period.staff-weekly', [
                'year'          => 2026,
                'month'         => 6,
                'staff'         => $other->id,
                'monthlyTarget' => $mt->id,
            ]))
            ->assertForbidden();
    }
}
