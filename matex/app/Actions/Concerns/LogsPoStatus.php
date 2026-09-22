<?php

namespace App\Actions\Concerns;

use App\Enums\PoStatus;
use App\Models\PoStatusLog;
use App\Models\PurchaseOrder;
use App\Models\User;

trait LogsPoStatus
{
    protected function logStatus(
        PurchaseOrder $po,
        ?PoStatus $from,
        PoStatus $to,
        string $action,
        ?User $user = null,
        ?string $notes = null,
        ?array $meta = null,
    ): void {
        PoStatusLog::create([
            'purchase_order_id' => $po->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'user_id' => $user?->id,
            'action' => $action,
            'notes' => $notes,
            'meta' => $meta,
        ]);
    }
}
