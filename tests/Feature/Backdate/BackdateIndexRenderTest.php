<?php
namespace Tests\Feature\Backdate;
use App\Models\BackdateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class BackdateIndexRenderTest extends TestCase
{
    use RefreshDatabase;
    public function test_leader_can_open_backdate_index_with_requests(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'operational']);
        $staff  = User::factory()->staff()->create(['department' => 'operational']);
        foreach (['pending','approved','rejected'] as $st) {
            BackdateRequest::factory()->create(['user_id' => $staff->id, 'status' => $st]);
        }
        $this->actingAs($leader)->get(route('backdate-requests.index'))->assertOk();
    }

    public function test_leader_sees_action_buttons_for_pending_request_without_status_filter(): void
    {
        $leader = User::factory()->leader()->create(['department' => 'operational']);
        $staff  = User::factory()->staff()->create(['department' => 'operational']);
        $pending = BackdateRequest::factory()->create(['user_id' => $staff->id, 'status' => 'pending']);

        $response = $this->actingAs($leader)->get(route('backdate-requests.index'));

        $response->assertOk()
            ->assertSee('Aksi')
            ->assertSee(route('backdate-requests.approve', $pending), false)
            ->assertSee(route('backdate-requests.reject', $pending), false);
    }
}
