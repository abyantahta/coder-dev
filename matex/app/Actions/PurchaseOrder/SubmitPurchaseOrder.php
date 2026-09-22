<?php

namespace App\Actions\PurchaseOrder;

use App\Actions\Concerns\LogsPoStatus;
use App\Enums\PoStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubmitPurchaseOrder
{
    use LogsPoStatus;

    public function execute(PurchaseOrder $po, User $user): PurchaseOrder
    {
        if ($po->status !== PoStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Hanya PO draft yang dapat disubmit.',
            ]);
        }

        if ($po->items()->count() === 0 || $po->schedules()->count() === 0) {
            throw ValidationException::withMessages([
                'items' => 'PO harus memiliki minimal 1 item dan 1 jadwal pengiriman.',
            ]);
        }

        $from = $po->status;
        $po->update([
            'status' => PoStatus::AwaitingRmConfirm,
            'submitted_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->logStatus($po, $from, PoStatus::AwaitingRmConfirm, 'submitted', $user);

        return $po->fresh();
    }
}
