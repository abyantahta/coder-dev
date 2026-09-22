<?php

namespace App\Actions\PurchaseOrder;

use App\Actions\Concerns\LogsPoStatus;
use App\Enums\PoStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmByRm
{
    use LogsPoStatus;

    public function execute(PurchaseOrder $po, User $user, array $data): PurchaseOrder
    {
        if ($po->status !== PoStatus::AwaitingRmConfirm) {
            throw ValidationException::withMessages([
                'status' => 'PO tidak dalam status menunggu konfirmasi RM.',
            ]);
        }

        return DB::transaction(function () use ($po, $user, $data) {
            foreach ($data['items'] as $itemData) {
                $poItem = $po->items()->whereKey($itemData['id'])->firstOrFail();
                $poItem->update([
                    'qty_confirmed' => $itemData['qty_confirmed'],
                ]);
            }

            foreach ($data['schedules'] as $scheduleData) {
                $schedule = $po->schedules()->whereKey($scheduleData['id'])->firstOrFail();
                $schedule->update([
                    'qty_confirmed' => $scheduleData['qty_confirmed'],
                    'scheduled_date' => $scheduleData['scheduled_date'] ?? $schedule->scheduled_date,
                ]);
            }

            $from = $po->status;
            $po->update([
                'status' => PoStatus::AwaitingPurchasingOk,
                'rm_confirmed_at' => now(),
            ]);

            $this->logStatus(
                $po,
                $from,
                PoStatus::AwaitingPurchasingOk,
                'rm_confirmed',
                $user,
                'Supplier RM mengonfirmasi qty dan jadwal'
            );

            return $po->fresh(['items.item', 'schedules']);
        });
    }
}
