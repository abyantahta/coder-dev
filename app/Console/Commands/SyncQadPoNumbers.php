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
        $pending = WoPartOrder::where('status', 'pr_created')
            ->whereNotNull('pr_number')
            ->whereNull('qad_po_no')
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
            $updated++;

            $this->info($result['po_no']
                ? "PR {$order->pr_number} -> PO {$result['po_no']} (approval_status={$result['approval_status']})"
                : "PR {$order->pr_number} -> approval_status={$result['approval_status']} (belum ada PO)");
        }

        $this->info("Selesai. Dicek: {$pending->count()}, di-update: {$updated}.");

        return self::SUCCESS;
    }
}
