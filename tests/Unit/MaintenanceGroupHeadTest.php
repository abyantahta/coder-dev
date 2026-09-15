<?php

namespace Tests\Unit;

use App\Models\MaintenanceGroup;
use App\Models\MaintenanceUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceGroupHeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_head_uses_loaded_users_relation(): void
    {
        $unit = MaintenanceUnit::create(['name' => 'Manufacturing']);
        $group = MaintenanceGroup::create(['name' => 'Group A', 'unit_id' => $unit->id]);
        $head = User::factory()->create([
            'name' => 'M Rochmat',
            'role' => 'group_head',
            'group_id' => $group->id,
            'unit_id' => $unit->id,
        ]);
        User::factory()->create([
            'role' => 'member',
            'group_id' => $group->id,
            'unit_id' => $unit->id,
        ]);

        $group->load('users');

        $this->assertNotNull($group->groupHead());
        $this->assertSame($head->id, $group->groupHead()->id);
        $this->assertSame('M Rochmat', $group->groupHead()->name);
    }

    public function test_group_without_head_returns_null(): void
    {
        $unit = MaintenanceUnit::create(['name' => 'Utility']);
        $group = MaintenanceGroup::create(['name' => 'Group B', 'unit_id' => $unit->id]);
        User::factory()->create(['role' => 'member', 'group_id' => $group->id, 'unit_id' => $unit->id]);

        $this->assertNull($group->groupHead());
    }
}
