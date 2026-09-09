<?php

namespace App\Services;

use App\Models\DepartmentStock;
use App\Models\Item;
use App\Models\ItemStock;
use App\Models\MemoStock;
use App\Models\MinimumStock;
use App\Models\ProcurementItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function __construct(private QXtendService $qxtend) {}

    /**
     * Tambah stok departemen (mis. barang masuk/return) — juga mencerminkan
     * total fisik di ItemStock. Dipakai bersama oleh Masuk Barang, Kembalikan
     * Barang, dan alur penerimaan GA.
     */
    public function creditDepartmentStock(Item $item, int $departmentId, float $qty): void
    {
        if ($qty <= 0) return;

        DepartmentStock::firstOrCreate(
            ['item_id' => $item->id, 'department_id' => $departmentId],
            ['qty_held' => 0]
        )->increment('qty_held', $qty);

        ItemStock::firstOrCreate(
            ['item_id' => $item->id, 'warehouse' => $item->warehouse],
            ['qty_on_hand' => 0, 'qty_reserved' => 0]
        );
        ItemStock::where('item_id', $item->id)->where('warehouse', $item->warehouse)
            ->increment('qty_on_hand', $qty);
    }

    /**
     * Kurangi stok departemen (mis. Ambil Barang, serah-terima GA) — divalidasi
     * cukup dulu, tidak boleh sampai minus.
     *
     * @throws \RuntimeException kalau stok departemen tidak mencukupi
     */
    public function debitDepartmentStock(Item $item, int $departmentId, float $qty): void
    {
        if ($qty <= 0) return;

        $deptStock = DepartmentStock::where('item_id', $item->id)
            ->where('department_id', $departmentId)
            ->first();

        if (!$deptStock || $deptStock->qty_held < $qty) {
            throw new \RuntimeException("Stok departemen untuk {$item->description} tidak mencukupi.");
        }

        $deptStock->decrement('qty_held', $qty);
        ItemStock::where('item_id', $item->id)->where('warehouse', $item->warehouse)
            ->decrement('qty_on_hand', $qty);
    }

    /**
     * Reduce QAD inventory stock after goods out approved
     */
    public function reduceQADStock(Transaction $transaction): array
    {
        if (!$transaction->department_id) {
            throw new \RuntimeException('Transaksi ini tidak punya departemen tujuan, tidak bisa menentukan stok mana yang dikurangi.');
        }

        $issueLines = [];

        foreach ($transaction->details as $detail) {
            $item = $detail->item;
            if ($item->is_memo) continue;

            $issueLines[] = [
                'item_code' => $item->item_code,
                'warehouse' => $item->warehouse,
                'qty'       => $detail->qty_approved,
                'unit'      => $item->unit,
                'reference' => $transaction->trans_no,
            ];

            $this->debitDepartmentStock($item, $transaction->department_id, $detail->qty_approved);
        }

        if (empty($issueLines)) {
            return ['success' => true, 'message' => 'No QAD items to issue'];
        }

        // Hanya kirim ke QAD jika sudah dikonfigurasi
        if (!$this->qxtend->isConfigured()) {
            return ['success' => true, 'message' => 'Local only — QAD not configured'];
        }

        return $this->qxtend->postInventoryIssue([
            'trans_no'   => $transaction->trans_no,
            'trans_date' => $transaction->trans_date->format('Y-m-d'),
            'department' => $transaction->department?->code,
            'lines'      => $issueLines,
        ]);
    }

    /**
     * Add/reduce memo stock
     */
    public function updateMemoStock(int $itemId, float $qty, string $type): void
    {
        $memoStock = MemoStock::firstOrCreate(
            ['item_id' => $itemId],
            ['qty_on_hand' => 0]
        );

        if ($type === 'in') {
            $memoStock->increment('qty_on_hand', $qty);
        } else {
            $memoStock->decrement('qty_on_hand', $qty);
        }

        $memoStock->update(['last_updated' => now()]);
    }

    /**
     * Check items below minimum stock and generate PR if needed
     */
    public function checkAndGeneratePR(int $adminId): ?PurchaseRequest
    {
        $belowMinItems = Item::with(['departmentStocks', 'minimumStock'])
            ->whereHas('minimumStock', fn($q) => $q->where('is_active', true))
            ->get()
            ->filter(fn($item) => $item->isBelowMinimum());

        if ($belowMinItems->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($belowMinItems, $adminId) {
            $pr = PurchaseRequest::create([
                'pr_no'      => PurchaseRequest::generatePRNo(),
                'status'     => 'draft',
                'notes'      => 'Auto-generated dari sistem minimum stock',
                'created_by' => $adminId,
            ]);

            foreach ($belowMinItems as $item) {
                $currentStock = $item->getQtyOnHand();
                $minStock     = $item->minimumStock->min_qty;
                $qtyNeeded    = $minStock - $currentStock + $minStock; // order up to 2x min

                PurchaseRequestDetail::create([
                    'pr_id'         => $pr->id,
                    'item_id'       => $item->id,
                    'qty_needed'    => max($qtyNeeded, 0),
                    'current_stock' => $currentStock,
                    'min_stock'     => $minStock,
                ]);
            }

            return $pr;
        });
    }

    /**
     * Cari/buat Item (katalog stok gudang) dari ProcurementItem (katalog kebutuhan GA/ATK) —
     * dua katalog ini terpisah, jembatan ini dipakai saat GA konfirmasi penerimaan barang.
     */
    public function resolveOrCreateWarehouseItem(ProcurementItem $pItem): Item
    {
        return Item::firstOrCreate(
            ['item_code' => $pItem->item_code],
            [
                'description' => $pItem->name,
                'unit'        => $pItem->uom->code,
                'category'    => $pItem->category?->name,
                'warehouse'   => 'WH01',
                'is_memo'     => false,
                'is_active'   => true,
            ]
        );
    }

    /**
     * Refresh stock from QAD for specific item
     */
    public function refreshItemStock(Item $item): bool
    {
        if ($item->is_memo) return false;
        if (!$this->qxtend->isConfigured()) return false;

        $qadStock = $this->qxtend->getItemStock($item->item_code, $item->warehouse);
        if (!$qadStock) return false;

        ItemStock::updateOrCreate(
            ['item_id' => $item->id, 'warehouse' => $item->warehouse],
            [
                'qty_on_hand'  => $qadStock['qty_on_hand'] ?? 0,
                'qty_reserved' => $qadStock['qty_reserved'] ?? 0,
                'last_sync_at' => now(),
            ]
        );

        return true;
    }
}
