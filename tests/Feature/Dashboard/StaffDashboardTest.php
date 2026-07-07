<?php

namespace Tests\Feature\Dashboard;

use App\Models\AppNotification;
use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejected_report_shows_callout_on_staff_dashboard(): void
    {
        $staff = User::factory()->staff()->department('sales')->create();

        $entry = DailyTaskEntry::factory()->forUser($staff)->verification('rejected')->create([
            'task_description' => 'Laporan yang ditolak leader',
            'rejection_note'   => 'Alasan unik penolakan ABC123',
            'reviewed_at'      => now(),
            'task_date'        => now()->subDays(3)->toDateString(),
        ]);

        $this->makeRejectedNotif($staff, $entry, read: false);

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Laporan Ditolak')
            ->assertSee('Alasan unik penolakan ABC123');
    }

    public function test_rejected_callout_hidden_once_notification_read(): void
    {
        $staff = User::factory()->staff()->department('sales')->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->verification('rejected')->create([
            'task_description' => 'Laporan ditolak sudah dibaca',
            'rejection_note'   => 'Alasan unik sudah dibaca XYZ789',
            'reviewed_at'      => now(),
            'task_date'        => now()->subDays(3)->toDateString(),
        ]);

        $this->makeRejectedNotif($staff, $entry, read: true);

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Alasan unik sudah dibaca XYZ789');
    }

    private function makeRejectedNotif(User $staff, DailyTaskEntry $entry, bool $read): void
    {
        AppNotification::create([
            'user_id'    => $staff->id,
            'type'       => AppNotification::TYPE_REPORT_REJECTED,
            'title'      => 'Laporan Ditolak',
            'body'       => 'Laporan ditolak permanen.',
            'related_id' => $entry->id,
            'read_at'    => $read ? now() : null,
        ]);
    }
}
