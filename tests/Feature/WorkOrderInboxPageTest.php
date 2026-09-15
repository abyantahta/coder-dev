<?php

namespace Tests\Feature;

use App\Models\ApprovalStep;
use App\Models\Department;
use App\Models\DepartmentRole;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderInboxPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_unit_head_lands_on_inbox_when_wo_wait_on_them(): void
    {
        [$uh, $pending, $running] = $this->seedUnitHeadQueue();

        $this->actingAs($uh)
            ->get(route('work-orders.index'))
            ->assertOk()
            ->assertSee('Perlu Tindakan')
            ->assertDontSee('menunggu tindakanmu')
            ->assertDontSee('bottleneck')
            ->assertDontSee('Semua Prioritas')
            ->assertSee('Group Head')
            ->assertSee('Tanggal dibuat')
            ->assertDontSee('>Filter</button>', false)
            ->assertSee($pending->wo_number)
            ->assertSee('Terima WO')
            ->assertDontSee($running->wo_number);
    }

    public function test_on_progress_tab_hides_inbox_items(): void
    {
        [$uh, $pending, $running] = $this->seedUnitHeadQueue();

        $this->actingAs($uh)
            ->get(route('work-orders.index', ['tab' => 'progress']))
            ->assertOk()
            ->assertSee($running->wo_number)
            ->assertDontSee($pending->wo_number);
    }

    public function test_history_tab_shows_member_sr(): void
    {
        [$uh, $pending, $running] = $this->seedUnitHeadQueue();
        $running->update(['status' => 'finished', 'score' => 85]);

        $this->actingAs($uh)
            ->get(route('work-orders.index', ['tab' => 'history']))
            ->assertOk()
            ->assertSee('SR')
            ->assertSee('85%')
            ->assertSee($running->wo_number)
            ->assertDontSee($pending->wo_number);
    }

    public function test_group_head_filter_limits_to_that_group(): void
    {
        [$uh, $groupA, $woA, $woB] = $this->seedGroupFilterQueue();

        $this->actingAs($uh)
            ->get(route('work-orders.index', ['tab' => 'progress', 'group_id' => $groupA->id]))
            ->assertOk()
            ->assertSee($woA->wo_number)
            ->assertDontSee($woB->wo_number)
            ->assertSee('Rochmat');
    }

    public function test_member_filter_is_ignored_when_not_in_selected_group(): void
    {
        [$uh, $groupA, $woA, $woB, $memberB] = $this->seedGroupFilterQueue();

        $this->actingAs($uh)
            ->get(route('work-orders.index', [
                'tab' => 'progress',
                'group_id' => $groupA->id,
                'member_id' => $memberB->id,
            ]))
            ->assertOk()
            ->assertSee($woA->wo_number)
            ->assertDontSee($woB->wo_number);
    }

    public function test_date_range_filter_uses_created_at(): void
    {
        [$uh, , $woA, $woB] = $this->seedGroupFilterQueue();
        $woA->forceFill(['created_at' => now()->subDays(10)])->save();
        $woB->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->actingAs($uh)
            ->get(route('work-orders.index', [
                'tab' => 'progress',
                'date_from' => now()->subDays(3)->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->assertSee($woB->wo_number)
            ->assertDontSee($woA->wo_number);
    }

    /**
     * @return array{0: User, 1: \App\Models\MaintenanceGroup, 2: WorkOrder, 3: WorkOrder, 4: User}
     */
    private function seedGroupFilterQueue(): array
    {
        $mtc = Department::create(['name' => 'Maintenance', 'code' => 'MTC', 'slug' => 'maintenance']);
        $uhRole = DepartmentRole::create(['department_id' => $mtc->id, 'name' => 'Unit Head', 'key' => 'unit_head']);
        $ghRole = DepartmentRole::create(['department_id' => $mtc->id, 'name' => 'Group Head', 'key' => 'group_head']);

        ApprovalStep::create([
            'department_id' => $mtc->id, 'step_order' => 4,
            'name' => 'Assign Member', 'actor_role_id' => $ghRole->id,
            'step_type' => 'assign', 'action_label' => 'Assign ke Member',
        ]);

        $unit = \App\Models\MaintenanceUnit::create(['name' => 'Manufacturing']);
        $groupA = \App\Models\MaintenanceGroup::create(['name' => 'Group A', 'unit_id' => $unit->id]);
        $groupB = \App\Models\MaintenanceGroup::create(['name' => 'Group B', 'unit_id' => $unit->id]);

        $requester = User::factory()->create();
        $uh = User::factory()->create([
            'role' => 'unit_head',
            'department_id' => $mtc->id,
            'dept_role_id' => $uhRole->id,
            'unit_id' => $unit->id,
        ]);
        User::factory()->create([
            'name' => 'Rochmat',
            'role' => 'group_head',
            'group_id' => $groupA->id,
            'unit_id' => $unit->id,
        ]);
        $memberA = User::factory()->create([
            'name' => 'Budi',
            'role' => 'member',
            'group_id' => $groupA->id,
            'unit_id' => $unit->id,
        ]);
        $memberB = User::factory()->create([
            'name' => 'Wisnu',
            'role' => 'member',
            'group_id' => $groupB->id,
            'unit_id' => $unit->id,
        ]);

        $woA = WorkOrder::create([
            'wo_number' => 'WO-MTC-TEST-00A1',
            'title' => 'WO group A',
            'description' => 'Test',
            'requester_id' => $requester->id,
            'destination' => 'maintenance',
            'status' => 'assigned_member',
            'target_department_id' => $mtc->id,
            'current_step_order' => 5,
            'assigned_group_id' => $groupA->id,
            'assigned_member_id' => $memberA->id,
        ]);
        $woB = WorkOrder::create([
            'wo_number' => 'WO-MTC-TEST-00B1',
            'title' => 'WO group B',
            'description' => 'Test',
            'requester_id' => $requester->id,
            'destination' => 'maintenance',
            'status' => 'assigned_member',
            'target_department_id' => $mtc->id,
            'current_step_order' => 5,
            'assigned_group_id' => $groupB->id,
            'assigned_member_id' => $memberB->id,
        ]);

        return [$uh, $groupA, $woA, $woB, $memberB];
    }

    /**
     * @return array{0: User, 1: WorkOrder, 2: WorkOrder}
     */
    private function seedUnitHeadQueue(): array
    {
        $mtc = Department::create(['name' => 'Maintenance', 'code' => 'MTC', 'slug' => 'maintenance']);
        $uhRole = DepartmentRole::create(['department_id' => $mtc->id, 'name' => 'Unit Head', 'key' => 'unit_head']);
        $ghRole = DepartmentRole::create(['department_id' => $mtc->id, 'name' => 'Group Head', 'key' => 'group_head']);

        ApprovalStep::create([
            'department_id' => $mtc->id, 'step_order' => 1,
            'name' => 'Penerimaan', 'actor_role_id' => $uhRole->id,
            'step_type' => 'standard', 'action_label' => 'Terima WO',
        ]);
        ApprovalStep::create([
            'department_id' => $mtc->id, 'step_order' => 4,
            'name' => 'Assign Member', 'actor_role_id' => $ghRole->id,
            'step_type' => 'assign', 'action_label' => 'Assign ke Member',
        ]);

        $requester = User::factory()->create();
        $uh = User::factory()->create([
            'role' => 'unit_head',
            'department_id' => $mtc->id,
            'dept_role_id' => $uhRole->id,
        ]);

        $pending = WorkOrder::create([
            'wo_number' => 'WO-MTC-TEST-0001',
            'title' => 'Mesin pending UH',
            'description' => 'Test',
            'requester_id' => $requester->id,
            'destination' => 'maintenance',
            'status' => 'pending',
            'target_department_id' => $mtc->id,
            'current_step_order' => 1,
        ]);

        $running = WorkOrder::create([
            'wo_number' => 'WO-MTC-TEST-0002',
            'title' => 'Sudah di group',
            'description' => 'Test',
            'requester_id' => $requester->id,
            'destination' => 'maintenance',
            'status' => 'assigned_group',
            'target_department_id' => $mtc->id,
            'current_step_order' => 4,
        ]);

        return [$uh, $pending, $running];
    }
}
