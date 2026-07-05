<?php

namespace Tests\Feature\Backdate;

use App\Models\BackdateRequest;
use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackdateTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_token_backdates_a_single_entry_then_is_consumed(): void
    {

        $this->travelTo(now()->setTime(10, 0));

        $staff = User::factory()->staff()->create();
        $date = now()->subDay()->toDateString();

        $bd = BackdateRequest::factory()
            ->forUser($staff)
            ->approvedWithToken('tok-abc')
            ->create(['requested_date' => $date]);

        $payload = [
            'task_description' => 'Pekerjaan kemarin yang belum dilaporkan',
            'priority' => 'medium',
            'duration_value' => 60,
            'duration_unit' => 'menit',
            'status' => 'selesai',
            'notes' => 'Detail pekerjaan kemarin.',
            'backdate_token' => 'tok-abc',
        ];

        $this->actingAs($staff)->post(route('daily-tasks.store'), $payload)->assertRedirect();
        $this->assertSame(1, DailyTaskEntry::where('user_id', $staff->id)
            ->whereDate('task_date', $date)->count());

        $this->assertNull(BackdateRequest::findValidByToken('tok-abc'));

        $this->actingAs($staff)->post(route('daily-tasks.store'), array_merge($payload, [
            'task_description' => 'Coba pakai token bekas',
        ]))->assertRedirect();

        $backdatedCount = DailyTaskEntry::where('user_id', $staff->id)
            ->whereDate('task_date', $date)
            ->count();

        $this->assertSame(1, $backdatedCount, 'Token backdate tidak boleh dipakai dua kali.');
    }
}
