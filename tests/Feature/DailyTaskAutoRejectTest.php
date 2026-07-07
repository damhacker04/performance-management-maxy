<?php

namespace Tests\Feature;

use App\Models\DailyTaskEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DailyTaskAutoRejectTest extends TestCase
{
    use RefreshDatabase;

    private function makeRevisionEntry(\DateTimeInterface|string|null $reviewedAt): DailyTaskEntry
    {
        $staff = User::factory()->create(['role' => 'staff', 'department' => 'operational']);

        return DailyTaskEntry::create([
            'user_id' => $staff->id,
            'task_description' => 'Contoh tugas',
            'priority' => 'medium',
            'duration_minutes' => 60,
            'status' => 'selesai',
            'task_date' => now()->toDateString(),
            'verification_status' => 'revision',
            'reviewed_at' => $reviewedAt,
        ]);
    }

    public function test_expired_revision_is_auto_rejected(): void
    {
        $entry = $this->makeRevisionEntry(now()->subHours(11));

        $this->assertTrue($entry->autoRejectExpiredRevision());
        $this->assertSame('rejected', $entry->fresh()->verification_status);
        $this->assertStringContainsString('Otomatis ditolak', $entry->fresh()->rejection_note);
    }

    public function test_revision_within_window_is_kept(): void
    {
        $entry = $this->makeRevisionEntry(now()->subHour());

        $this->assertFalse($entry->autoRejectExpiredRevision());
        $this->assertSame('revision', $entry->fresh()->verification_status);
    }

    public function test_non_revision_entry_is_untouched(): void
    {
        $entry = $this->makeRevisionEntry(now()->subHours(11));
        $entry->update(['verification_status' => 'pending']);

        $this->assertFalse($entry->autoRejectExpiredRevision());
        $this->assertSame('pending', $entry->fresh()->verification_status);
    }
}
