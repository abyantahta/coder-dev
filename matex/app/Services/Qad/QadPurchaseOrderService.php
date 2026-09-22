<?php

namespace App\Services\Qad;

use App\Models\PurchaseOrder;

class QadPurchaseOrderService
{
    public function __construct(private readonly QadSoapClient $client) {}

    /**
     * Push a matex PO to QAD (maintainPurchaseOrder) once Supplier RM has
     * confirmed quantities — qty_confirmed, not qty_ordered, since that's
     * the value that becomes fixed the moment it lands in QAD (a draft's
     * qty_ordered can still change before RM confirms, so pushing then
     * would risk a QAD PO going stale). Item number (podPart), quantity
     * (podQtyOrd/podQtyOpen), vendor (poVend) and dates are derived from
     * the real PO; site/location/account/etc are shared config defaults
     * (config('qad.defaults')) — see QadReceivingService for the same
     * defaults used at receipt time.
     *
     * podType=P / podLoc=RAWMAT (not S/SUBCONT) — matex doesn't track QAD
     * Work Orders, and podType=S requires a real Work Order at receipt time
     * (verified: with S/SUBCONT, receivePurchaseOrder fails with "Work
     * Order/ID does not exist" no matter what's sent for worklot; switching
     * to P/RAWMAT receives cleanly with no warnings at all).
     *
     * @return array{success: bool, payload: array, response: array, qad_po_number: ?string, line_numbers: array<int, int>}
     */
    public function createFromMatexPo(PurchaseOrder $po): array
    {
        $po->loadMissing('items.item', 'supplierRm');

        $defaults = config('qad.defaults');
        $today = now()->format('Y-m-d');
        $dueDate = $po->due_date->format('Y-m-d');

        $lines = '';
        $payloadLines = [];

        foreach ($po->items as $index => $poItem) {
            $line = $index + 1;
            $part = $this->esc($poItem->item->item_number);
            $confirmedQty = (float) ($poItem->qty_confirmed ?? $poItem->qty_ordered);
            $qty = number_format($confirmedQty, 5, '.', '');
            $um = $this->esc($poItem->item->uom ?: 'KG');

            $payloadLines[] = [
                'purchase_order_item_id' => $poItem->id,
                'line' => $line,
                'part' => $poItem->item->item_number,
                'qty' => $confirmedQty,
                'um' => $poItem->item->uom,
            ];

            $lines .= <<<XML

                <qsvc:lineDetail>
                <qsvc:poNbr/>
                <qsvc:line>{$line}</qsvc:line>
                <qsvc:podSite>{$defaults['site']}</qsvc:podSite>
                <qsvc:podPart>{$part}</qsvc:podPart>
                <qsvc:podQtyOrd>{$qty}</qsvc:podQtyOrd>
                <qsvc:podQtyOpen>{$qty}</qsvc:podQtyOpen>
                <qsvc:podUm>{$um}</qsvc:podUm>
                <qsvc:podDueDate>{$dueDate}</qsvc:podDueDate>
                <qsvc:podPurCost>{$defaults['purchase_cost']}</qsvc:podPurCost>
                <qsvc:podDiscPct>0.00</qsvc:podDiscPct>
                <qsvc:podLoc>{$defaults['location']}</qsvc:podLoc>
                <qsvc:podPerDate>{$dueDate}</qsvc:podPerDate>
                <qsvc:podNeed>{$dueDate}</qsvc:podNeed>
                <qsvc:podFixPr>false</qsvc:podFixPr>
                <qsvc:podAcct>{$defaults['account']}</qsvc:podAcct>
                <qsvc:podType>{$defaults['po_type']}</qsvc:podType>
                <qsvc:podTaxable>true</qsvc:podTaxable>
                <qsvc:podTaxc>{$defaults['tax_code']}</qsvc:podTaxc>
                <qsvc:podInspRqd>false</qsvc:podInspRqd>
                <qsvc:podUmConv>1.0000</qsvc:podUmConv>
                <qsvc:podCstUp>true</qsvc:podCstUp>
                <qsvc:podTaxIn>false</qsvc:podTaxIn>
                <qsvc:routeop>0</qsvc:routeop>
                </qsvc:lineDetail>
                XML;
        }

        $session = $this->client->sessionContextXml();
        $vendor = $this->esc($po->supplierRm->code);
        $remarks = $this->esc("Matex {$po->po_number}");

        $body = <<<XML
            <qsvc:maintainPurchaseOrder>
            {$session}
            <qsvc:dsPurchaseOrder>
            <qsvc:purchaseOrder>
            <qsvc:poNbr/>
            <qsvc:poVend>{$vendor}</qsvc:poVend>
            <qsvc:poShip>{$defaults['site']}</qsvc:poShip>
            <qsvc:poOrdDate>{$today}</qsvc:poOrdDate>
            <qsvc:poDueDate>{$dueDate}</qsvc:poDueDate>
            <qsvc:poBill>{$defaults['site']}</qsvc:poBill>
            <qsvc:poRmks>{$remarks}</qsvc:poRmks>
            <qsvc:disc>0.00</qsvc:disc>
            <qsvc:poSite>{$defaults['site']}</qsvc:poSite>
            <qsvc:poDaybookset>{$defaults['daybookset']}</qsvc:poDaybookset>
            <qsvc:poConfirm>true</qsvc:poConfirm>
            <qsvc:impexp>false</qsvc:impexp>
            <qsvc:poCurr>{$defaults['currency']}</qsvc:poCurr>
            <qsvc:poLang>us</qsvc:poLang>
            <qsvc:poTaxable>true</qsvc:poTaxable>
            <qsvc:poTaxc>{$defaults['tax_code']}</qsvc:poTaxc>
            <qsvc:poFixPr>false</qsvc:poFixPr>
            <qsvc:poConsignment>false</qsvc:poConsignment>
            <qsvc:poCrTerms>{$defaults['credit_terms']}</qsvc:poCrTerms>
            <qsvc:poCrtInt>0.00</qsvc:poCrtInt>
            <qsvc:poReqId>{$defaults['requester_id']}</qsvc:poReqId>
            <qsvc:poTaxUsage/>
            <qsvc:poTaxEnv>{$defaults['tax_env']}</qsvc:poTaxEnv>
            <qsvc:taxIn>false</qsvc:taxIn>{$lines}
            </qsvc:purchaseOrder>
            </qsvc:dsPurchaseOrder>
            </qsvc:maintainPurchaseOrder>
            XML;

        $result = $this->client->call('maintainPurchaseOrder', 'urn:services-qad-com:SDI_BuatPO', $body);
        $qadPoNumber = $this->extractPoNumber($result['response'] ?? '');
        $qadResult = $this->extractResult($result['response'] ?? '');

        return [
            'success' => $result['successful'] && $qadResult !== 'error' && $qadPoNumber !== null,
            'payload' => ['lines' => $payloadLines],
            'response' => [
                'result' => $qadResult,
                'po_number' => $qadPoNumber,
                'raw' => $result['response'] ?? null,
            ],
            'qad_po_number' => $qadPoNumber,
            // purchase_order_item_id => QAD line number, so Receiving can
            // later tell QAD which line a receipt applies to.
            'line_numbers' => collect($payloadLines)->pluck('line', 'purchase_order_item_id')->all(),
        ];
    }

    private function extractPoNumber(string $body): ?string
    {
        if (preg_match('/<(?:\w+:)?poNbr>([^<]+)<\/(?:\w+:)?poNbr>/i', $body, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function extractResult(string $body): ?string
    {
        if (preg_match('/<(?:\w+:)?result>([^<]+)<\/(?:\w+:)?result>/i', $body, $m)) {
            return strtolower(trim($m[1]));
        }

        return null;
    }

    private function esc(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
