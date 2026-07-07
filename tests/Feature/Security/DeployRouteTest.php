<?php

namespace Tests\Feature\Security;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeployRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_deploy_update_without_token_is_not_found(): void
    {
        config(['app.deploy_token' => 'super-secret-token']);

        $this->get('/deploy-update')->assertNotFound();
    }

    public function test_deploy_update_with_wrong_token_is_not_found(): void
    {
        config(['app.deploy_token' => 'super-secret-token']);

        $this->get('/deploy-update?token=salah')->assertNotFound();
    }

    public function test_deploy_update_is_disabled_when_token_unset(): void
    {
        config(['app.deploy_token' => null]);

        $this->get('/deploy-update?token=anything')->assertNotFound();
    }

    public function test_deploy_update_with_correct_token_runs(): void
    {
        config(['app.deploy_token' => 'super-secret-token']);

        $this->get('/deploy-update?token=super-secret-token')
            ->assertOk()
            ->assertSee('Berhasil');

        $this->assertDatabaseHas('users', ['email' => 'isaac.maxy.academy@gmail.com']);
    }

    public function test_seed_demo_without_token_is_not_found(): void
    {
        config(['app.deploy_token' => 'super-secret-token']);

        $this->get('/seed-demo')->assertNotFound();
        $this->get('/seed-demo?key=maxy-demo-2026')->assertNotFound();
    }
}
