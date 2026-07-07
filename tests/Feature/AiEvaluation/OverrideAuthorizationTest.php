<?php

namespace Tests\Feature\AiEvaluation;

use App\Models\AiEvaluation;
use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverrideAuthorizationTest extends TestCase
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

    private function evaluationForDept(string $dept): AiEvaluation
    {
        $staff = User::factory()->staff()->department($dept)->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->create();

        return AiEvaluation::factory()->forEntry($entry)->create();
    }

    public function test_leader_can_override_own_department_evaluation(): void
    {
        $leader = User::factory()->leader()->department('sales')->create();
        $eval = $this->evaluationForDept('sales');

        $this->actingAs($leader)
            ->post(route('ai.evaluations.override.store', $eval), [
                'new_score' => 8,
                'reason' => 'Bukti kerja sebenarnya lebih baik dari penilaian AI.',
            ])
            ->assertRedirect();

        $this->assertTrue($eval->fresh()->is_overridden);
        $this->assertDatabaseHas('leader_overrides', ['ai_evaluation_id' => $eval->id]);
    }

    public function test_leader_cannot_override_other_department_evaluation(): void
    {
        $leader = User::factory()->leader()->department('sales')->create();
        $eval = $this->evaluationForDept('finance');

        $this->actingAs($leader)
            ->post(route('ai.evaluations.override.store', $eval), [
                'new_score' => 8,
                'reason' => 'Mencoba mengubah nilai staf departemen lain.',
            ])
            ->assertForbidden();

        $this->assertFalse($eval->fresh()->is_overridden);
    }
}
