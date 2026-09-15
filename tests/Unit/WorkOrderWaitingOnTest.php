<?php

namespace Tests\Unit;

use App\Models\ApprovalStep;
use App\Models\Department;
use App\Models\DepartmentRole;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderWaitingOnTest extends TestCase
{
    use RefreshDatabase;

    private Department $mtc;
    private DepartmentRole $uhRole;
    private DepartmentRole $ghRole;
    private DepartmentRole $memberRole;
    private User $uh;
    private User $gh;
    private User $member;
    private User $requester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mtc = Department::create([
            'name' => 'Maintenance', 'code' => 'MTC', 'slug' => 'maintenance',
        ]);

        $this->uhRole = DepartmentRole::create([
            'department_id' => $this->mtc->id, 'name' => 'Unit Head', 'key' => 'unit_head',
        ]);
        $this->ghRole = DepartmentRole::create([
            'department_id' => $this->mtc->id, 'name' => 'Group Head', 'key' => 'group_head',
        ]);
        $this->memberRole = DepartmentRole::create([
            'department_id' => $this->mtc->id, 'name' => 'Member', 'key' => 'member',
        ]);
        $shRole = DepartmentRole::create([
            'department_id' => $this->mtc->id, 'name' => 'Section Head', 'key' => 'section_head',
        ]);

        ApprovalStep::create([
            'department_id' => $this->mtc->id, 'step_order' => 1,
            'name' => 'Penerimaan', 'actor_role_id' => $this->uhRole->id,
            'step_type' => 'standard', 'action_label' => 'Terima WO',
        ]);
        ApprovalStep::create([
            'department_id' => $this->mtc->id, 'step_order' => 2,
            'name' => 'Cek Parts', 'actor_role_id' => $this->uhRole->id,
            'step_type' => 'spare_parts_check',
        ]);
        ApprovalStep::create([
            'department_id' => $this->mtc->id, 'step_order' => 3,
            'name' => 'Assign Group', 'actor_role_id' => $this->uhRole->id,
            'step_type' => 'assign', 'assigns_to_role_key' => 'group_head',
            'action_label' => 'Assign ke Group Head',
        ]);
        ApprovalStep::create([
            'department_id' => $this->mtc->id, 'step_order' => 4,
            'name' => 'Assign Member', 'actor_role_id' => $this->ghRole->id,
            'step_type' => 'assign', 'assigns_to_role_key' => 'member',
            'action_label' => 'Assign ke Member',
        ]);
        ApprovalStep::create([
            'department_id' => $this->mtc->id, 'step_order' => 5,
            'name' => 'Pengerjaan', 'step_type' => 'completion',
        ]);
        ApprovalStep::create([
            'department_id' => $this->mtc->id, 'step_order' => 6,
            'name' => 'Review', 'step_type' => 'requester_review',
        ]);

        $this->requester = User::factory()->create();
        $this->uh = User::factory()->create([
            'role' => 'unit_head',
            'department_id' => $this->mtc->id,
            'dept_role_id' => $this->uhRole->id,
        ]);
        $this->gh = User::factory()->create([
            'role' => 'group_head',
            'department_id' => $this->mtc->id,
            'dept_role_id' => $this->ghRole->id,
        ]);
        $this->member = User::factory()->create([
            'role' => 'member',
            'department_id' => $this->mtc->id,
            'dept_role_id' => $this->memberRole->id,
        ]);
        User::factory()->create([
            'role' => 'section_head',
            'department_id' => $this->mtc->id,
            'dept_role_id' => $shRole->id,
        ]);
    }

    public function test_unit_head_inbox_is_pending_and_accepted_not_assigned_group(): void
    {
        $pending = $this->makeWo('pending', 1);
        $accepted = $this->makeWo('accepted', 2);
        $waitingAssign = $this->makeWo('accepted', 3);
        $atGroup = $this->makeWo('assigned_group', 4);
        $this->makeWo('pending_parts', 2);
        $this->makeWo('finished', 1);

        $ids = WorkOrder::waitingOn($this->uh)->pluck('id');

        $this->assertTrue($ids->contains($pending->id));
        $this->assertTrue($ids->contains($accepted->id));
        $this->assertTrue($ids->contains($waitingAssign->id));
        $this->assertFalse($ids->contains($atGroup->id));
        $this->assertCount(3, $ids);
    }

    public function test_group_head_inbox_is_assigned_group_only(): void
    {
        $this->makeWo('pending', 1);
        $mine = $this->makeWo('assigned_group', 4);

        $ids = WorkOrder::waitingOn($this->gh)->pluck('id');

        $this->assertEquals([$mine->id], $ids->all());
    }

    public function test_member_inbox_is_assigned_or_rework(): void
    {
        $this->makeWo('assigned_group', 4);
        $active = $this->makeWo('assigned_member', 5, $this->member->id);
        $rework = $this->makeWo('rework', 5, $this->member->id);
        $this->makeWo('assigned_member', 5, User::factory()->create(['role' => 'member'])->id);

        $ids = WorkOrder::waitingOn($this->member)->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertTrue($ids->contains($rework->id));
        $this->assertCount(2, $ids);
    }

    public function test_requester_inbox_is_completed_review(): void
    {
        $done = $this->makeWo('completed', 6, $this->member->id);
        $this->makeWo('assigned_member', 5, $this->member->id);

        $ids = WorkOrder::waitingOn($this->requester)->pluck('id');

        $this->assertEquals([$done->id], $ids->all());
    }

    public function test_section_head_inbox_includes_warehouse_queue(): void
    {
        $sh = User::where('role', 'section_head')->first();
        $parts = $this->makeWo('pending_parts', 2);
        $this->makeWo('pending', 1);

        $ids = WorkOrder::waitingOn($sh)->pluck('id');

        $this->assertEquals([$parts->id], $ids->all());
    }

    public function test_needed_action_label_follows_step(): void
    {
        $wo = $this->makeWo('pending', 1);
        $this->assertSame('Terima WO', $wo->neededActionLabel());

        $wo = $this->makeWo('accepted', 3);
        $this->assertSame('Assign ke Group Head', $wo->neededActionLabel());

        $wo = $this->makeWo('pending_parts', 2);
        $this->assertSame('Kelola pemesanan part', $wo->neededActionLabel());
    }

    private function makeWo(string $status, int $step, ?int $memberId = null): WorkOrder
    {
        return WorkOrder::create([
            'wo_number'            => WorkOrder::generateWoNumber('MTC'),
            'title'                => $status.' step '.$step,
            'description'          => 'Test',
            'requester_id'         => $this->requester->id,
            'destination'          => 'maintenance',
            'status'               => $status,
            'target_department_id' => $this->mtc->id,
            'current_step_order'   => $step,
            'assigned_member_id'   => $memberId,
        ]);
    }
}
