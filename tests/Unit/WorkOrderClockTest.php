<?php

namespace Tests\Unit;

use App\Models\WorkOrder;
use Carbon\Carbon;
use Tests\TestCase;

class WorkOrderClockTest extends TestCase
{
    public function test_clock_is_frozen_only_when_status_is_completed(): void
    {
        $completed = new WorkOrder(['status' => 'completed']);
        $assigned  = new WorkOrder(['status' => 'assigned_member']);
        $rework    = new WorkOrder(['status' => 'rework']);
        $finished  = new WorkOrder(['status' => 'finished']);

        $this->assertTrue($completed->isClockFrozen());
        $this->assertFalse($assigned->isClockFrozen());
        $this->assertFalse($rework->isClockFrozen());
        $this->assertFalse($finished->isClockFrozen());
    }

    public function test_frozen_clock_uses_actual_end_not_now(): void
    {
        $frozenAt = Carbon::parse('2026-09-14 10:00:00');
        $wo = new WorkOrder([
            'status'        => 'completed',
            'actual_end_at' => $frozenAt,
            'completed_at'  => $frozenAt,
        ]);

        $this->assertTrue($wo->clockAsOf()->equalTo($frozenAt));
    }

    public function test_running_clock_uses_current_time(): void
    {
        Carbon::setTestNow('2026-09-15 11:00:00');
        $wo = new WorkOrder(['status' => 'assigned_member']);

        $this->assertTrue($wo->clockAsOf()->equalTo(Carbon::parse('2026-09-15 11:00:00')));
        Carbon::setTestNow();
    }

    public function test_completed_wo_overdue_is_based_on_frozen_time(): void
    {
        Carbon::setTestNow('2026-09-20 12:00:00');

        $wo = new WorkOrder([
            'status'        => 'completed',
            'deadline'      => Carbon::parse('2026-09-16 17:00:00'),
            'actual_end_at' => Carbon::parse('2026-09-15 16:00:00'),
            'completed_at'  => Carbon::parse('2026-09-15 16:00:00'),
        ]);

        $this->assertFalse($wo->isOverdue());
        Carbon::setTestNow();
    }

    public function test_completed_wo_that_finished_late_stays_overdue(): void
    {
        Carbon::setTestNow('2026-09-20 12:00:00');

        $wo = new WorkOrder([
            'status'        => 'completed',
            'deadline'      => Carbon::parse('2026-09-14 17:00:00'),
            'actual_end_at' => Carbon::parse('2026-09-16 10:00:00'),
            'completed_at'  => Carbon::parse('2026-09-16 10:00:00'),
        ]);

        $this->assertTrue($wo->isOverdue());
        Carbon::setTestNow();
    }

    public function test_finished_status_is_never_overdue(): void
    {
        $wo = new WorkOrder([
            'status'   => 'finished',
            'deadline' => Carbon::parse('2026-01-01'),
        ]);

        $this->assertFalse($wo->isOverdue());
    }

    public function test_freeze_actual_end_sets_completed_and_actual_end(): void
    {
        $at = Carbon::parse('2026-09-14 15:30:00');
        $wo = new WorkOrder(['status' => 'assigned_member']);

        $this->assertEquals($at, $wo->freezeActualEnd($at)['completed_at']);
        $this->assertEquals($at, $wo->freezeActualEnd($at)['actual_end_at']);
    }

    public function test_unfreeze_for_rework_clears_frozen_times(): void
    {
        $wo = new WorkOrder();
        $cleared = $wo->unfreezeForRework();

        $this->assertNull($cleared['completed_at']);
        $this->assertNull($cleared['actual_end_at']);
    }

    public function test_plan_snapshot_is_written_once_unless_overwritten(): void
    {
        $start = Carbon::parse('2026-09-14 08:00:00');
        $end   = Carbon::parse('2026-09-16 17:00:00');
        $wo = new WorkOrder();

        $first = $wo->planSnapshot($start, $end);
        $this->assertEquals($start, $first['planned_start_at']);
        $this->assertEquals($end, $first['planned_end_at']);
        $this->assertEquals($start, $first['actual_start_at']);

        $wo->planned_end_at = $end;
        $wo->actual_start_at = $start;

        $second = $wo->planSnapshot(
            Carbon::parse('2026-09-20 08:00:00'),
            Carbon::parse('2026-09-22 17:00:00')
        );
        $this->assertSame([], $second);

        $overwrite = $wo->planSnapshot(
            Carbon::parse('2026-09-20 08:00:00'),
            Carbon::parse('2026-09-22 17:00:00'),
            true
        );
        $this->assertTrue($overwrite['planned_end_at']->equalTo(Carbon::parse('2026-09-22 17:00:00')));
    }

    public function test_plan_snapshot_empty_when_end_missing(): void
    {
        $wo = new WorkOrder();

        $this->assertSame([], $wo->planSnapshot(now(), null));
    }

    public function test_clear_plan_nulls_planning_and_actual_fields(): void
    {
        $cleared = (new WorkOrder())->clearPlan();

        $this->assertNull($cleared['planned_start_at']);
        $this->assertNull($cleared['planned_end_at']);
        $this->assertNull($cleared['actual_start_at']);
        $this->assertNull($cleared['actual_end_at']);
        $this->assertNull($cleared['completed_at']);
    }
}
