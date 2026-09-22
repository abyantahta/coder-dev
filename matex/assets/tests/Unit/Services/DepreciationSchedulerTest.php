<?php

namespace Tests\Unit\Services;

use App\Services\DepreciationScheduler;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DepreciationSchedulerTest extends TestCase
{
    private DepreciationScheduler $scheduler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->scheduler = new DepreciationScheduler();
    }

    public function test_runs_one_extra_row_past_full_payoff_when_no_disposal(): void
    {
        // cost divides evenly by the monthly amount (3 x 1,000,000 = 3,000,000):
        // the schedule still emits a 4th row because the loop's tolerance check
        // (depreciation - cost <= 1) treats "exactly equal" as "still due".
        $schedule = $this->scheduler->buildSchedule(
            cost: 3_000_000,
            depreciationPerMonth: 1_000_000,
            serviceDate: Carbon::parse('2024-01-10'),
            disposalDate: null,
            asOf: Carbon::parse('2030-01-01') // far enough out that it never binds
        );

        $this->assertCount(4, $schedule);
        $this->assertSame([0.0, 1_000_000.0, 2_000_000.0, 3_000_000.0], array_column($schedule, 'depreciation'));
        $this->assertSame([3_000_000.0, 2_000_000.0, 1_000_000.0, 0.0], array_column($schedule, 'nbv'));
    }

    public function test_stops_at_disposal_date_even_if_cost_not_fully_depreciated(): void
    {
        $start = Carbon::parse('2024-01-10')->endOfMonth();
        $disposal = $start->copy()->addMonth()->addMonth();

        $schedule = $this->scheduler->buildSchedule(
            cost: 10_000_000,
            depreciationPerMonth: 1_000_000,
            serviceDate: Carbon::parse('2024-01-10'),
            disposalDate: $disposal,
            asOf: Carbon::parse('2030-01-01')
        );

        $this->assertCount(2, $schedule);
        $this->assertSame([0.0, 1_000_000.0], array_column($schedule, 'depreciation'));
        $this->assertSame([10_000_000.0, 9_000_000.0], array_column($schedule, 'nbv'));
    }

    public function test_caps_the_final_rows_depreciation_at_cost(): void
    {
        $schedule = $this->scheduler->buildSchedule(
            cost: 1_000_000,
            depreciationPerMonth: 500_000.4,
            serviceDate: Carbon::parse('2024-01-10'),
            disposalDate: null,
            asOf: Carbon::parse('2030-01-01')
        );

        $this->assertCount(3, $schedule);
        $this->assertSame([0.0, 500_000.4, 1_000_000.0], array_column($schedule, 'depreciation'));
        $this->assertSame(0.0, $schedule[2]['nbv']);
    }

    public function test_stops_immediately_when_asof_is_before_service_month_ends(): void
    {
        $schedule = $this->scheduler->buildSchedule(
            cost: 1_000_000,
            depreciationPerMonth: 100_000,
            serviceDate: Carbon::parse('2024-06-15'),
            disposalDate: null,
            asOf: Carbon::parse('2024-06-01')
        );

        $this->assertSame([], $schedule);
    }
}
