<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpenRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function leader(): User
    {
        return User::factory()->leader()->create(['department' => 'operational']);
    }

    private function validPayload(): array
    {
        return [
            'title' => 'Target Operasional',
            'month' => (int) now()->month,
            'year'  => (int) now()->year,
        ];
    }

    public function test_store_ignores_external_back_url(): void
    {
        $leader = $this->leader();

        $this->actingAs($leader)
            ->post(route('monthly-targets.store', ['back' => 'https://evil.com/phish']), $this->validPayload())
            ->assertRedirect(route('monthly-targets.index'));
    }

    public function test_store_ignores_protocol_relative_back_url(): void
    {
        $leader = $this->leader();

        $this->actingAs($leader)
            ->post(route('monthly-targets.store', ['back' => '//evil.com']), $this->validPayload())
            ->assertRedirect(route('monthly-targets.index'));
    }

    public function test_store_keeps_valid_relative_back_url(): void
    {
        $leader = $this->leader();

        $this->actingAs($leader)
            ->post(route('monthly-targets.store', ['back' => '/monthly-targets?month=6']), $this->validPayload())
            ->assertRedirect('/monthly-targets?month=6');
    }

    public function test_store_keeps_same_host_absolute_back_url(): void
    {
        $leader = $this->leader();
        $backUrl = url('/monthly-targets?month=3&year=2026');

        $this->actingAs($leader)
            ->post(route('monthly-targets.store', ['back' => $backUrl]), $this->validPayload())
            ->assertRedirect($backUrl);
    }
}
