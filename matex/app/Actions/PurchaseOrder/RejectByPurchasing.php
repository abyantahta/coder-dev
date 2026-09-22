<?php

namespace App\Actions\PurchaseOrder;

use App\Actions\Concerns\LogsPoStatus;
use App\Enums\PoStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RejectByPurchasing
{
    use LogsPoStatus;

    public function execute(PurchaseOrder $po, User $user, string $reason): PurchaseOrder
    {
        if ($po->status !== PoStatus::AwaitingPurchasingOk) {
            throw ValidationException::withMessages([
                'status' => 'PO tidak menunggu persetujuan Purchasing.',
            ]);
        }

        $from = $po->status;
        $po->update([
            'status' => PoStatus::AwaitingRmConfirm,
            'rejection_reason' => $reason,
            'rm_confirmed_at' => null,
        ]);

        // Deliberately NOT resetting items/schedules qty_confirmed here —
        // Supplier RM should see and revise their own last-submitted
        // numbers, not lose them back to Purchasing's original draft plan.

        $this->logStatus(
            $po,
            $from,
            PoStatus::AwaitingRmConfirm,
            'purchasing_rejected',
            $user,
            $reason
        );

        return $po->fresh();
    }
}
