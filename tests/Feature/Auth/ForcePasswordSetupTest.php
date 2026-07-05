<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForcePasswordSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_password_can_view_setup_form(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)->get(route('password.setup'))->assertOk();
    }

    public function test_user_with_existing_password_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('password.setup'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_guest_is_redirected_from_setup_form(): void
    {
        $this->get(route('password.setup'))->assertRedirect(route('login'));
    }

    public function test_user_can_set_password_via_setup_form(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)->post(route('password.setup.store'), [
            'password'              => 'NewSecurePass123!',
            'password_confirmation' => 'NewSecurePass123!',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->password);
    }

    public function test_password_must_be_confirmed(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)->post(route('password.setup.store'), [
            'password'              => 'NewSecurePass123!',
            'password_confirmation' => 'DifferentPass456!',
        ])->assertSessionHasErrors('password');
    }

    public function test_password_must_meet_minimum_requirements(): void
    {
        $user = User::factory()->create(['password' => null]);

        $this->actingAs($user)->post(route('password.setup.store'), [
            'password'              => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
    }
}
