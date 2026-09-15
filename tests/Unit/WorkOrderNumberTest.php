<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkOrderNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_mtc_number_of_the_month_starts_at_0001(): void
    {
        $ym = now()->format('Ym');
        $this->assertSame("WO-MTC-{$ym}-0001", WorkOrder::generateWoNumber('MTC'));
    }

    public function test_sequence_is_per_department(): void
    {
        $requester = User::factory()->create();
        $ym = now()->format('Ym');

        WorkOrder::create([
            'wo_number'    => "WO-MTC-{$ym}-0007",
            'title'        => 'Dummy MTC',
            'description'  => 'Dummy',
            'requester_id' => $requester->id,
        ]);

        $this->assertSame("WO-MTC-{$ym}-0008", WorkOrder::generateWoNumber('maintenance'));
        $this->assertSame("WO-GA-{$ym}-0001", WorkOrder::generateWoNumber('ga'));
        $this->assertSame("WO-QA-{$ym}-0001", WorkOrder::generateWoNumber('qa'));
    }

    public function test_dept_code_maps_slug_and_model_code(): void
    {
        $this->assertSame('MTC', WorkOrder::deptCode('maintenance'));
        $this->assertSame('GA', WorkOrder::deptCode('GA'));
        $this->assertSame('QA', WorkOrder::deptCode('qa'));
    }
}
