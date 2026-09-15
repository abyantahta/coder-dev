<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_sr_is_average_score_of_finished_maintenance_wo(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $requester = User::factory()->create();

        $this->makeFinished($requester, $member, 'maintenance', 100);
        $this->makeFinished($requester, $member, 'maintenance', 80);
        $this->makeFinished($requester, $member, 'ga', 10);

        $this->assertSame(90.0, $member->service_rate);
    }

    public function test_member_without_finished_wo_has_zero_sr(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->assertSame(0.0, $member->service_rate);
    }

    public function test_qa_member_sr_only_counts_qa_destination(): void
    {
        $qa = User::factory()->create(['role' => 'qa_member']);
        $requester = User::factory()->create();

        $this->makeFinished($requester, $qa, 'qa', 70);
        $this->makeFinished($requester, $qa, 'maintenance', 100);

        $this->assertSame(70.0, $qa->service_rate);
    }

    public function test_group_head_sr_is_average_of_member_sr(): void
    {
        $unit = \App\Models\MaintenanceUnit::create(['name' => 'Manufacturing']);
        $group = \App\Models\MaintenanceGroup::create(['name' => 'Group A', 'unit_id' => $unit->id]);
        $head = User::factory()->create(['role' => 'group_head', 'group_id' => $group->id, 'unit_id' => $unit->id]);
        $m1 = User::factory()->create(['role' => 'member', 'group_id' => $group->id, 'unit_id' => $unit->id]);
        $m2 = User::factory()->create(['role' => 'member', 'group_id' => $group->id, 'unit_id' => $unit->id]);
        $requester = User::factory()->create();

        $this->makeFinished($requester, $m1, 'maintenance', 100);
        $this->makeFinished($requester, $m2, 'maintenance', 80);

        $this->assertSame(90.0, $head->service_rate);
    }

    private function makeFinished(User $requester, User $member, string $destination, int $score): WorkOrder
    {
        return WorkOrder::create([
            'wo_number'          => WorkOrder::generateWoNumber(),
            'title'              => 'WO '.$score,
            'description'        => 'Test',
            'requester_id'       => $requester->id,
            'assigned_member_id' => $member->id,
            'destination'        => $destination,
            'status'             => 'finished',
            'score'              => $score,
        ]);
    }
}
