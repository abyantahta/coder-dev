<?php

namespace Tests\Unit\Support;

use App\Support\StoProgress;
use PHPUnit\Framework\TestCase;

class StoProgressTest extends TestCase
{
    public function test_returns_zero_instead_of_dividing_by_zero(): void
    {
        $this->assertSame(0.0, StoProgress::ratio(0, 0));
    }

    public function test_computes_a_partial_ratio(): void
    {
        $this->assertSame(0.5, StoProgress::ratio(5, 10));
    }

    public function test_computes_a_complete_ratio(): void
    {
        $this->assertSame(1.0, StoProgress::ratio(10, 10));
    }
}
