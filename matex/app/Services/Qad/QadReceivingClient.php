<?php

namespace App\Services\Qad;

use App\Models\Receiving;

/**
 * Real QadClientInterface implementation — pushes a Receiving to QAD via
 * receivePurchaseOrder (see QadReceivingService), the same operation
 * warehouse's QadSoapService uses for its receiving flow.
 */
class QadReceivingClient implements QadClientInterface
{
    public function __construct(private readonly QadReceivingService $service) {}

    public function receive(Receiving $receiving): array
    {
        $receiving->loadMissing([
            'deliveryNote.purchaseOrder',
            'deliveryNote.purchaseOrderItem.item',
        ]);

        $dn = $receiving->deliveryNote;
        $po = $dn->purchaseOrder;
        $poItem = $dn->purchaseOrderItem;

        if (blank($po->qad_po_number)) {
            return [
                'success' => false,
                'payload' => [],
                'response' => ['error' => 'PO belum tersinkron ke QAD (qad_po_number kosong) — belum bisa receiving.'],
            ];
        }

        if (blank($poItem->qad_line_number)) {
            return [
                'success' => false,
                'payload' => [],
                'response' => ['error' => 'Line item ini belum punya nomor line QAD (qad_line_number kosong).'],
            ];
        }

        return $this->service->receivePurchaseOrder(
            $po->qad_po_number,
            [[
                'line' => $poItem->qad_line_number,
                'qty' => (float) $receiving->received_qty,
            ]],
        );
    }
}
