<?php

namespace Tests\Unit;

use App\Services\ApprovalService;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class ApprovalWorkingDaysTest extends TestCase
{
    private function addWorkingDays(Carbon $from, int $days): Carbon
    {
        $method = new ReflectionMethod(ApprovalService::class, 'addWorkingDays');

        return $method->invoke(new ApprovalService(), $from, $days);
    }

    public function test_skips_saturday_and_sunday(): void
    {
        // 11 Sep 2026 is Friday
        $result = $this->addWorkingDays(Carbon::parse('2026-09-11 08:00:00'), 1);

        $this->assertSame('2026-09-14', $result->toDateString());
        $this->assertFalse($result->isWeekend());
    }

    public function test_three_working_days_from_friday_lands_on_wednesday(): void
    {
        $result = $this->addWorkingDays(Carbon::parse('2026-09-11 08:00:00'), 3);

        $this->assertSame('2026-09-16', $result->toDateString());
    }

    public function test_does_not_mutate_source_date(): void
    {
        $source = Carbon::parse('2026-09-14 08:00:00');
        $this->addWorkingDays($source, 2);

        $this->assertSame('2026-09-14 08:00:00', $source->format('Y-m-d H:i:s'));
    }
}
