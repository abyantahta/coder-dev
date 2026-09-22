<?php

namespace App\Policies;

use App\Enums\PoStatus;
use App\Enums\UserRole;
use App\Models\DeliverySchedule;
use App\Models\User;

class DeliverySchedulePolicy
{
    public function generateDn(User $user, DeliverySchedule $schedule): bool
    {
        $po = $schedule->purchaseOrder;

        return $user->hasRole(UserRole::SupplierRm)
            && $po
            && $user->company_id
            && (int) $po->supplier_rm_id === (int) $user->company_id
            && in_array($po->status, [PoStatus::Confirmed, PoStatus::InProgress], true)
            && $schedule->deliveryNote === null;
    }
}
