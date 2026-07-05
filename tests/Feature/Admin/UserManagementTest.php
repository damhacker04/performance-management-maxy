<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_passes_user_management_authorization(): void
    {

        $cLevel = User::factory()->cLevel()->create();
        $this->actingAs($cLevel)->get(route('admin.users.index'))->assertForbidden();

        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($superAdmin)->get(route('admin.users.index'))->assertOk();
    }

    public function test_super_admin_user_index_renders_full_content(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $staff      = User::factory()->staff()->create(['name' => 'Budi Staf', 'department' => 'sales']);

        $this->actingAs($superAdmin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Manajemen Pengguna')
            ->assertSee('Budi Staf');
    }

    public function test_super_admin_create_form_renders_with_password_field(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Tambah Karyawan')
            ->assertSee('name="password"', false);
    }

    public function test_super_admin_can_create_user_with_password_and_user_can_login(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name'       => 'Karyawan Baru',
            'email'      => 'karyawan.baru@example.com',
            'role'       => 'staff',
            'department' => 'product_it',
            'password'   => 'rahasia123',
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'karyawan.baru@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->password);
        $this->assertTrue(Hash::check('rahasia123', $user->password));

        $this->assertTrue(\Illuminate\Support\Facades\Auth::attempt([
            'email'    => 'karyawan.baru@example.com',
            'password' => 'rahasia123',
        ]));
    }

    public function test_create_user_without_password_keeps_null_for_google_flow(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name'       => 'Tanpa Password',
            'email'      => 'tanpa.pass@example.com',
            'role'       => 'staff',
            'department' => 'product_it',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertNull(User::where('email', 'tanpa.pass@example.com')->first()->password);
    }

    public function test_update_with_password_changes_it_and_blank_keeps_it(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->staff()->create(['password' => 'lama12345']);
        $oldHash = $user->fresh()->password;

        $this->actingAs($superAdmin)->put(route('admin.users.update', $user), [
            'name'  => 'Nama Diubah',
            'email' => $user->email,
            'role'  => 'staff',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertSame($oldHash, $user->fresh()->password);
        $this->assertSame('Nama Diubah', $user->fresh()->name);

        $this->actingAs($superAdmin)->put(route('admin.users.update', $user), [
            'name'     => 'Nama Diubah',
            'email'    => $user->email,
            'role'     => 'staff',
            'password' => 'baru123456',
        ])->assertRedirect(route('admin.users.index'));
        $this->assertTrue(Hash::check('baru123456', $user->fresh()->password));
    }

    public function test_cannot_create_super_admin_via_form(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->post(route('admin.users.store'), [
            'name' => 'Hacker',
            'email' => 'hacker@example.com',
            'role' => 'super_admin',
            'department' => 'product_it',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'hacker@example.com']);
    }

    public function test_super_admin_cannot_deactivate_self(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.toggle-active', $superAdmin))
            ->assertRedirect();

        $this->assertTrue($superAdmin->fresh()->is_active);
    }

    public function test_super_admin_can_deactivate_staff(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $staff = User::factory()->staff()->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.toggle-active', $staff))
            ->assertRedirect();

        $this->assertFalse($staff->fresh()->is_active);
    }
}
