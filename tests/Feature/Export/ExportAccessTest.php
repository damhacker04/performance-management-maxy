<?php

namespace Tests\Feature\Export;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_plain_staff_cannot_access_export(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('export.index'))->assertForbidden();
    }

    public function test_c_level_can_access_export(): void
    {
        $exec = User::factory()->cLevel()->create();

        $this->actingAs($exec)->get(route('export.index'))->assertOk();
    }

    public function test_management_staff_can_access_export(): void
    {

        $mgmt = User::factory()->staff()->management()->create();

        $this->actingAs($mgmt)->get(route('export.index'))->assertOk();
    }
}
