<?php

namespace App\Actions\PurchaseOrder;

use App\Actions\Concerns\LogsPoStatus;
use App\Actions\Concerns\ResolvesQadItem;
use App\Actions\Concerns\ResolvesQadSupplier;
use App\Enums\PoStatus;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StorePurchaseOrder
{
    use LogsPoStatus;
    use ResolvesQadItem;
    use ResolvesQadSupplier;

    public function execute(User $user, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($user, $data) {
            $supplierRm = $this->resolveSupplierFromQadCode($data['supplier_code']);

            $po = PurchaseOrder::create([
                'po_number' => $this->generateDraftNumber(),
                'supplier_rm_id' => $supplierRm->id,
                'due_date' => $data['due_date'],
                'status' => PoStatus::Draft,
                'created_by' => $user->id,
                'notes' => $data['notes'] ?? null,
            ]);

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

            $this->logStatus($po, null, PoStatus::Draft, 'created', $user);

            return $po->load(['items.item', 'schedules.ohpSupplier', 'supplierRm']);
        });
    }

    /**
     * DR-{YYYYMM}-{seq} — a placeholder shown while the PO is still a
     * draft/revision (qty not fixed yet). It gets replaced by the real QAD
     * PO number once Purchasing approves and QAD accepts it (see
     * ApproveByPurchasing) — so DR- vs a real QAD number visually shows
     * which POs are still draft. Sequence resets each month; retried on a
     * rare concurrent-create collision (unique constraint on po_number).
     */
    private function generateDraftNumber(): string
    {
        $prefix = 'DR-'.now()->format('Ym').'-';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $last = PurchaseOrder::where('po_number', 'like', $prefix.'%')
                ->orderByDesc('po_number')
                ->value('po_number');

            $seq = $last ? ((int) substr($last, -4)) + 1 + $attempt : 1 + $attempt;
            $candidate = $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

            if (! PurchaseOrder::where('po_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return $prefix.now()->format('His');
    }
}
