<?php

namespace Tests\Unit;

use App\Models\WorkOrder;
use Carbon\Carbon;
use Tests\TestCase;

class WorkOrderScoreTest extends TestCase
{
    private function wo(array $attrs = []): WorkOrder
    {
        return new WorkOrder(array_merge([
            'deadline'     => Carbon::parse('2026-09-10 17:00:00'),
            'completed_at' => Carbon::parse('2026-09-10 16:00:00'),
            'rework_count' => 0,
        ], $attrs));
    }

    public function test_on_time_without_rework_scores_100(): void
    {
        $this->assertSame(100, $this->wo()->calculateScore());
    }

    public function test_early_completion_still_scores_100(): void
    {
        $wo = $this->wo(['completed_at' => Carbon::parse('2026-09-08 10:00:00')]);

        $this->assertSame(100, $wo->calculateScore());
    }

    public function test_one_day_late_penalizes_10_points(): void
    {
        $wo = $this->wo(['completed_at' => Carbon::parse('2026-09-11 18:00:00')]);

        $this->assertSame(90, $wo->calculateScore());
    }

    public function test_three_days_late_penalizes_30_points(): void
    {
        $wo = $this->wo(['completed_at' => Carbon::parse('2026-09-13 18:00:00')]);

        $this->assertSame(70, $wo->calculateScore());
    }

    public function test_each_rework_penalizes_15_points(): void
    {
        $wo = $this->wo(['rework_count' => 2]);

        $this->assertSame(70, $wo->calculateScore());
    }

    public function test_late_and_rework_penalties_stack(): void
    {
        $wo = $this->wo([
            'completed_at' => Carbon::parse('2026-09-13 18:00:00'),
            'rework_count' => 1,
        ]);

        $this->assertSame(55, $wo->calculateScore());
    }

    public function test_score_floor_is_10(): void
    {
        $wo = $this->wo([
            'completed_at' => Carbon::parse('2026-09-25 18:00:00'),
            'rework_count' => 5,
        ]);

        $this->assertSame(10, $wo->calculateScore());
    }

    public function test_missing_deadline_is_treated_as_on_time(): void
    {
        $wo = $this->wo(['deadline' => null, 'completed_at' => now()]);

        $this->assertSame(100, $wo->calculateScore());
    }
}
