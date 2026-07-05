<?php

namespace Tests\Feature\Notification;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_read_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $notif = AppNotification::create([
            'user_id' => $owner->id,
            'type' => 'system',
            'title' => 'Privasi',
            'body' => 'Notifikasi milik owner.',
        ]);

        $this->actingAs($attacker)
            ->get(route('notifications.read', $notif))
            ->assertForbidden();

        $this->assertNull($notif->fresh()->read_at);
    }

    public function test_read_all_only_affects_own_notifications(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $ownNotif = AppNotification::create([
            'user_id' => $owner->id, 'type' => 'system', 'title' => 'A', 'body' => 'a',
        ]);
        $otherNotif = AppNotification::create([
            'user_id' => $other->id, 'type' => 'system', 'title' => 'B', 'body' => 'b',
        ]);

        $this->actingAs($owner)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertNotNull($ownNotif->fresh()->read_at);
        $this->assertNull($otherNotif->fresh()->read_at);
    }
}
