<?php

namespace Tests\Feature\AiEvaluation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ValidateLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {

        putenv('GROQ_API_KEY=test-key');
        $_ENV['GROQ_API_KEY'] = 'test-key';
        $_SERVER['GROQ_API_KEY'] = 'test-key';
        parent::setUp();
    }

    protected function tearDown(): void
    {
        putenv('GROQ_API_KEY');
        unset($_ENV['GROQ_API_KEY'], $_SERVER['GROQ_API_KEY']);
        parent::tearDown();
    }

    public function test_guest_cannot_validate_link(): void
    {
        $this->post(route('ai.validate-link'), ['url' => 'https://docs.google.com/document/d/x/edit'])
            ->assertRedirect(route('login'));
    }

    public function test_internal_url_disguised_as_google_is_not_fetched(): void
    {
        Http::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai.validate-link'), [
            'url' => 'http://169.254.169.254/?docs.google.com',
        ]);

        $response->assertOk();

        Http::assertNothingSent();
    }

    public function test_public_google_link_is_reported_accessible(): void
    {
        Http::fake([
            'docs.google.com/*' => Http::response('isi dokumen', 200),
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai.validate-link'), [
            'url' => 'https://docs.google.com/document/d/abc123/edit',
        ]);

        $response->assertOk()->assertJson(['status' => 'public']);
    }
}
