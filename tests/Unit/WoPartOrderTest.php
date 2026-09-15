<?php

namespace Tests\Unit;

use App\Models\WoPartOrder;
use Carbon\Carbon;
use Tests\TestCase;

class WoPartOrderTest extends TestCase
{
    public function test_status_labels(): void
    {
        $this->assertSame('Menunggu Warehouse', WoPartOrder::statusLabel('pending_warehouse'));
        $this->assertSame('PR Dibuat (QAD)', WoPartOrder::statusLabel('pr_created'));
        $this->assertSame('Barang Diterima', WoPartOrder::statusLabel('received'));
    }

    public function test_overdue_when_expected_arrival_passed_and_not_received(): void
    {
        Carbon::setTestNow('2026-09-15 10:00:00');
        $order = new WoPartOrder([
            'status'            => 'pr_created',
            'expected_arrival'  => Carbon::parse('2026-09-01'),
        ]);

        $this->assertTrue($order->isOverdue());
        Carbon::setTestNow();
    }

    public function test_received_order_is_not_overdue(): void
    {
        Carbon::setTestNow('2026-09-15 10:00:00');
        $order = new WoPartOrder([
            'status'           => 'received',
            'expected_arrival' => Carbon::parse('2026-09-01'),
        ]);

        $this->assertFalse($order->isOverdue());
        Carbon::setTestNow();
    }

    public function test_procurement_days_null_until_both_dates_exist(): void
    {
        $order = new WoPartOrder(['pr_date' => '2026-09-01']);

        $this->assertNull($order->procurement_days);
    }

    public function test_procurement_days_counts_calendar_span(): void
    {
        $order = new WoPartOrder([
            'pr_date'     => '2026-09-01',
            'received_at' => '2026-09-11 08:00:00',
        ]);

        $this->assertSame(10, $order->procurement_days);
    }
}
