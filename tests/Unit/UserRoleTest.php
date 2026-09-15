<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\WorkOrder;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    public function test_role_helpers(): void
    {
        $this->assertTrue((new User(['role' => 'section_head']))->isSectionHead());
        $this->assertTrue((new User(['role' => 'unit_head']))->isUnitHead());
        $this->assertTrue((new User(['role' => 'group_head']))->isGroupHead());
        $this->assertTrue((new User(['role' => 'member']))->isMember());
        $this->assertTrue((new User(['role' => 'warehouse_mtc']))->isWarehouseMtc());
        $this->assertTrue((new User(['role' => 'qa_member']))->isQaMember());
        $this->assertTrue((new User(['role' => 'ga_section_head']))->isGaSectionHead());
        $this->assertTrue((new User(['role' => 'qa_group_head']))->isQaStaff());
        $this->assertFalse((new User(['role' => 'member']))->isSectionHead());
    }

    public function test_warehouse_actors(): void
    {
        $this->assertTrue((new User(['role' => 'warehouse_mtc']))->canActAsWarehouse());
        $this->assertTrue((new User(['role' => 'section_head']))->canActAsWarehouse());
        $this->assertTrue((new User(['role' => 'ga_section_head']))->canActAsWarehouse());
        $this->assertFalse((new User(['role' => 'member']))->canActAsWarehouse());
        $this->assertFalse((new User(['role' => 'user']))->canActAsWarehouse());
    }

    public function test_manages_warehouse_only_for_same_department(): void
    {
        $gaHead = new User(['role' => 'ga_section_head', 'department_id' => 3]);
        $same   = new WorkOrder(['target_department_id' => 3]);
        $other  = new WorkOrder(['target_department_id' => 1]);

        $this->assertTrue($gaHead->managesWarehouseFor($same));
        $this->assertFalse($gaHead->managesWarehouseFor($other));
        $this->assertFalse((new User(['role' => 'member', 'department_id' => 3]))->managesWarehouseFor($same));
    }

    public function test_maintenance_staff_roles(): void
    {
        $this->assertTrue((new User(['role' => 'member']))->isMaintenanceStaff());
        $this->assertTrue((new User(['role' => 'group_head']))->isMaintenanceStaff());
        $this->assertFalse((new User(['role' => 'warehouse_mtc']))->isMaintenanceStaff());
        $this->assertFalse((new User(['role' => 'ga_section_head']))->isMaintenanceStaff());
    }

    public function test_role_labels(): void
    {
        $this->assertSame('Section Head', User::roleLabel('section_head'));
        $this->assertSame('GA Section Head', User::roleLabel('ga_section_head'));
        $this->assertSame('Warehouse MTC', User::roleLabel('warehouse_mtc'));
        $this->assertSame('User', User::roleLabel('user'));
        $this->assertSame('Staff', User::roleLabel('staff'));
    }

    public function test_superadmin_flag(): void
    {
        $this->assertTrue((new User(['is_superadmin' => true]))->isSuperAdmin());
        $this->assertFalse((new User(['is_superadmin' => false]))->isSuperAdmin());
    }

    public function test_unknown_role_has_zero_service_rate(): void
    {
        $this->assertSame(0.0, (new User(['role' => 'user']))->service_rate);
        $this->assertSame(0.0, (new User(['role' => 'section_head']))->service_rate);
        $this->assertSame(0.0, (new User(['role' => 'warehouse_mtc']))->service_rate);
    }
}
