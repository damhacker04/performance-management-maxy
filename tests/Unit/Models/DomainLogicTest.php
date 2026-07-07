<?php

namespace Tests\Unit\Models;

use App\Models\DailyTaskEntry;
use App\Models\KpiWeightSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainLogicTest extends TestCase
{
    use RefreshDatabase;

    public function test_revision_window_boundary(): void
    {
        $staff = User::factory()->staff()->create();

        $within = DailyTaskEntry::factory()->forUser($staff)
            ->revision(now()->subHours(9))->create();
        $expired = DailyTaskEntry::factory()->forUser($staff)
            ->revision(now()->subHours(11))->create();

        $this->assertTrue($within->canBeRevised());
        $this->assertFalse($expired->canBeRevised());
    }

    public function test_approved_entry_cannot_be_edited(): void
    {
        $staff = User::factory()->staff()->create();
        $entry = DailyTaskEntry::factory()->forUser($staff)->approved()->create();

        $this->assertFalse($entry->canBeEdited());
    }

    public function test_user_role_helpers(): void
    {
        $this->assertTrue(User::factory()->cLevel()->make()->isExecutive());
        $this->assertTrue(User::factory()->superAdmin()->make()->isExecutive());
        $this->assertFalse(User::factory()->leader()->make()->isExecutive());

        $this->assertTrue(User::factory()->leader()->make()->isLeadership());
        $this->assertFalse(User::factory()->staff()->make()->isLeadership());

        $this->assertTrue(User::factory()->cLevel()->make()->canExport());
        $this->assertTrue(User::factory()->staff()->management()->make()->canExport());
        $this->assertFalse(User::factory()->staff()->make()->canExport());
    }

    public function test_kpi_weight_setting_default_is_valid_25_each(): void
    {
        $setting = KpiWeightSetting::getActive();

        $this->assertEquals(25.0, (float) $setting->weight_achievement);
        $this->assertTrue($setting->isTotalValid());
    }
}
