<?php

namespace Tests\Unit;

use App\Models\WorkOrder;
use Tests\TestCase;

class WorkOrderStatusTest extends TestCase
{
    public function test_status_labels_cover_core_workflow(): void
    {
        $this->assertSame('Pending', WorkOrder::statusLabel('pending'));
        $this->assertSame('Diterima', WorkOrder::statusLabel('accepted'));
        $this->assertSame('Menunggu Parts', WorkOrder::statusLabel('pending_parts'));
        $this->assertSame('PR Dibuat (QAD)', WorkOrder::statusLabel('parts_ordered'));
        $this->assertSame('Dalam Pengerjaan', WorkOrder::statusLabel('assigned_member'));
        $this->assertSame('Selesai (Review)', WorkOrder::statusLabel('completed'));
        $this->assertSame('Rework', WorkOrder::statusLabel('rework'));
        $this->assertSame('Finished', WorkOrder::statusLabel('finished'));
    }

    public function test_unknown_status_is_ucfirst(): void
    {
        $this->assertSame('Mystery', WorkOrder::statusLabel('mystery'));
    }

    public function test_priority_colors(): void
    {
        $this->assertSame('tone-neutral', WorkOrder::priorityColor('low'));
        $this->assertSame('tone-steel', WorkOrder::priorityColor('medium'));
        $this->assertSame('tone-flame', WorkOrder::priorityColor('high'));
        $this->assertSame('tone-brick', WorkOrder::priorityColor('urgent'));
    }

    public function test_status_dot_follows_status_color(): void
    {
        $this->assertSame('tone-dot tone-dot-gold', WorkOrder::statusDot('pending'));
        $this->assertSame('tone-dot tone-dot-forest', WorkOrder::statusDot('finished'));
    }
}
