<?php

namespace App\Policies;

use App\Enums\PoStatus;
use App\Enums\UserRole;
use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        if ($user->isSdiStaff() || $user->hasRole(UserRole::Admin)) {
            return true;
        }

        if ($user->hasRole(UserRole::SupplierRm)) {
            return $user->company_id
                && (int) $purchaseOrder->supplier_rm_id === (int) $user->company_id;
        }

        if ($user->hasRole(UserRole::SupplierOhp)) {
            return $user->company_id
                && $purchaseOrder->schedules()
                    ->where('ohp_supplier_id', $user->company_id)
                    ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Purchasing, UserRole::Admin);
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole(UserRole::Purchasing, UserRole::Admin)
            && $purchaseOrder->status === PoStatus::Draft;
    }

    public function submit(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->update($user, $purchaseOrder);
    }

    public function confirmAsRm(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole(UserRole::SupplierRm)
            && $user->company_id
            && (int) $purchaseOrder->supplier_rm_id === (int) $user->company_id
            && $purchaseOrder->status === PoStatus::AwaitingRmConfirm;
    }

    public function approveAsPurchasing(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->hasRole(UserRole::Purchasing, UserRole::Admin)
            && $purchaseOrder->status === PoStatus::AwaitingPurchasingOk;
    }

    public function rejectAsPurchasing(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $this->approveAsPurchasing($user, $purchaseOrder);
    }
}
