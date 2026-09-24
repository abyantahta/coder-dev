<?php

namespace App\Console\Commands;

use App\Models\WoPartOrder;
use App\Services\Qad\QadRequisitionService;
use Illuminate\Console\Command;

class SyncQadPoNumbers extends Command
{
    protected $signature = 'qad:sync-po-numbers';

    protected $description = 'Check QAD (SDI_getPRtoPO_) for PRs already sent that have no PO number yet, and fill it in once approved';

    public function handle(QadRequisitionService $qad): int
    {
        // "Still needs checking" means at least one line has no PO number
        // yet — not just the order-level qad_po_no, since QAD can split a
        // PR across more than one PO and that field stays null on a split
        // (there's no single "the" PO to put there).
        $pending = WoPartOrder::where('status', 'pr_created')
            ->whereNotNull('pr_number')
            ->whereHas('lines', fn ($q) => $q->whereNull('qad_po_no'))
            ->with('lines')
            ->get();

        if ($pending->isEmpty()) {
            $this->info('Tidak ada PR yang perlu dicek.');

            return self::SUCCESS;
        }

        $updated = 0;

        foreach ($pending as $order) {
            $result = $qad->findPurchaseOrder($order->pr_number);

            if (! $result) {
                continue;
            }

            $order->update([
                'qad_approval_status' => $result['approval_status'],
                'qad_po_no' => $result['po_no'] ?? $order->qad_po_no,
            ]);

            foreach ($order->lines as $i => $line) {
                $lineResult = $result['lines'][$i + 1] ?? null;
                if ($lineResult && $lineResult['po_no']) {
                    $line->update(['qad_po_no' => $lineResult['po_no'], 'qad_po_status' => $lineResult['po_status']]);
                }
            }
            $updated++;

            $this->info(match (true) {
                $result['is_split'] => "PR {$order->pr_number} -> split ".count(array_unique(array_filter(array_column($result['lines'], 'po_no'))))." PO (approval_status={$result['approval_status']})",
                (bool) $result['po_no'] => "PR {$order->pr_number} -> PO {$result['po_no']} (approval_status={$result['approval_status']})",
                default => "PR {$order->pr_number} -> approval_status={$result['approval_status']} (belum ada PO)",
            });
        }

        $this->info("Selesai. Dicek: {$pending->count()}, di-update: {$updated}.");

        return self::SUCCESS;
    }
}
