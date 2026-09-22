<?php

namespace App\Actions\PurchaseOrder;

use App\Actions\Concerns\ResolvesQadItem;
use App\Actions\Concerns\ResolvesQadSupplier;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdatePurchaseOrder
{
    use ResolvesQadItem;
    use ResolvesQadSupplier;

    public function execute(PurchaseOrder $po, User $user, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data) {
            $supplierRm = $this->resolveSupplierFromQadCode($data['supplier_code']);

            $po->update([
                'po_number' => $data['po_number'],
                'supplier_rm_id' => $supplierRm->id,
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $po->schedules()->delete();
            $po->items()->delete();

            foreach ($data['items'] as $itemData) {
                $item = $this->resolveItemFromQadCode($itemData['item_number']);

                $poItem = $po->items()->create([
                    'item_id' => $item->id,
                    'qty_ordered' => $itemData['qty_ordered'],
                ]);

                foreach ($itemData['schedules'] as $schedule) {
                    $ohpSupplier = $this->resolveSupplierFromQadCode($schedule['ohp_supplier_code']);

                    $po->schedules()->create([
                        'purchase_order_item_id' => $poItem->id,
                        'ohp_supplier_id' => $ohpSupplier->id,
                        'scheduled_date' => $schedule['scheduled_date'],
                        'qty' => $schedule['qty'],
                        'status' => 'planned',
                    ]);
                }
            }

            return $po->fresh(['items.item', 'schedules.ohpSupplier', 'supplierRm']);
        });
    }
}
