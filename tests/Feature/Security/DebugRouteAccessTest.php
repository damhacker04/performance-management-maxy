<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugRouteAccessTest extends TestCase
{
    use RefreshDatabase;

    private array $paths = [
        '/debug/logs',
        '/debug/run-migration',
        '/debug/unassigned-targets',
    ];

    public function test_guest_is_redirected_to_login(): void
    {
        foreach ($this->paths as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_non_super_admin_is_forbidden(): void
    {
        $actors = [
            User::factory()->leader()->create(),
            User::factory()->staff()->create(),
            User::factory()->cLevel()->create(),
        ];

        foreach ($actors as $actor) {
            foreach ($this->paths as $path) {
                $this->actingAs($actor)->get($path)->assertForbidden();
            }
        }
    }

    public function test_super_admin_can_access_log_and_unassigned_routes(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/debug/logs')->assertOk();
        $this->actingAs($admin)->get('/debug/unassigned-targets')->assertOk();
    }
}
