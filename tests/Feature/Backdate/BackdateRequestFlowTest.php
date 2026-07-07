<?php

namespace Tests\Feature\Backdate;

use App\Models\BackdateRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackdateRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_backdate_request_form(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->get(route('backdate-requests.create'))->assertOk();
    }

    public function test_staff_can_submit_backdate_request(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->post(route('backdate-requests.store'), [
            'requested_date' => now()->subDay()->toDateString(),
            'reason'         => 'Lupa input laporan kemarin karena ada kegiatan mendadak.',
        ])->assertRedirect();

        $this->assertDatabaseHas('backdate_requests', [
            'user_id' => $staff->id,
            'status'  => 'pending',
        ]);
    }

    public function test_backdate_request_requires_reason(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->post(route('backdate-requests.store'), [
            'requested_date' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('reason');
    }

    public function test_backdate_request_requires_valid_date(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->post(route('backdate-requests.store'), [
            'requested_date' => 'bukan-tanggal',
            'reason'         => 'Alasan valid.',
        ])->assertSessionHasErrors('requested_date');
    }

    public function test_leader_can_view_backdate_requests_index(): void
    {
        $leader = User::factory()->leader()->create();
        $this->actingAs($leader)->get(route('backdate-requests.index'))->assertOk();
    }

    public function test_staff_cannot_access_backdate_requests_index(): void
    {
        $staff = User::factory()->staff()->create();
        $this->actingAs($staff)->get(route('backdate-requests.index'))->assertForbidden();
    }

    public function test_leader_can_approve_backdate_request(): void
    {
        $leader  = User::factory()->leader()->create();
        $staff   = User::factory()->staff()->create();
        $request = BackdateRequest::factory()->forUser($staff)->create(['status' => 'pending']);

        $this->actingAs($leader)
            ->patch(route('backdate-requests.approve', $request))
            ->assertRedirect();

        $updated = $request->fresh();
        $this->assertSame('approved', $updated->status);
        $this->assertNotNull($updated->approval_token);
        $this->assertNotNull($updated->token_expires_at);
    }

    public function test_staff_cannot_approve_backdate_request(): void
    {
        $staff   = User::factory()->staff()->create();
        $request = BackdateRequest::factory()->forUser($staff)->create(['status' => 'pending']);

        $this->actingAs($staff)
            ->patch(route('backdate-requests.approve', $request))
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status);
    }

    public function test_leader_can_reject_backdate_request(): void
    {
        $leader  = User::factory()->leader()->create();
        $staff   = User::factory()->staff()->create();
        $request = BackdateRequest::factory()->forUser($staff)->create(['status' => 'pending']);

        $this->actingAs($leader)
            ->patch(route('backdate-requests.reject', $request), [
                'rejection_note' => 'Tidak memenuhi syarat.',
            ])
            ->assertRedirect();

        $this->assertSame('rejected', $request->fresh()->status);
    }
}
