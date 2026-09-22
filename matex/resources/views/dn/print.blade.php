<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>DN {{ $note->dn_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 24px;
            background: #fff;
        }
        .sheet {
            max-width: 800px;
            margin: 0 auto;
            border: 2px solid #0f172a;
            padding: 28px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #0f766e;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }
        .brand h1 {
            margin: 0;
            font-size: 28px;
            letter-spacing: 0.08em;
            color: #0f766e;
        }
        .brand p { margin: 4px 0 0; color: #475569; font-size: 13px; }
        .dn-meta { text-align: right; }
        .dn-meta .label { font-size: 11px; text-transform: uppercase; color: #64748b; }
        .dn-meta .value { font-size: 22px; font-weight: 700; }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        .box {
            border: 1px solid #cbd5e1;
            padding: 12px 14px;
            min-height: 90px;
        }
        .box h3 {
            margin: 0 0 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
        }
        .box p { margin: 2px 0; font-size: 14px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #cbd5e1;
            padding: 10px 12px;
            text-align: left;
            font-size: 14px;
        }
        th { background: #f1f5f9; font-size: 12px; text-transform: uppercase; }
        .footer {
            margin-top: 28px;
            display: flex;
            justify-content: space-between;
            gap: 24px;
        }
        .sign {
            width: 30%;
            text-align: center;
            font-size: 12px;
        }
        .sign .line {
            margin-top: 64px;
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
        }
        .barcode {
            margin-top: 16px;
            text-align: center;
            font-family: "Courier New", monospace;
            font-size: 18px;
            letter-spacing: 0.2em;
            border: 1px dashed #94a3b8;
            padding: 10px;
        }
        .actions { margin: 0 auto 16px; max-width: 800px; text-align: right; }
        .actions button {
            background: #0f766e;
            color: #fff;
            border: 0;
            padding: 10px 18px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        @media print {
            .actions { display: none; }
            body { padding: 0; }
            .sheet { border: 2px solid #000; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">Cetak DN</button>
    </div>

    <div class="sheet">
        <div class="header">
            <div class="brand">
                <h1>MATEX</h1>
                <p>Material Exchange Portal — PT. SDI</p>
                <p>Delivery Note / Surat Jalan Material</p>
            </div>
            <div class="dn-meta">
                <div class="label">Nomor DN</div>
                <div class="value">{{ $note->dn_number }}</div>
                <div style="margin-top:8px;font-size:13px;">
                    Tanggal Pengiriman:<br>
                    <strong>{{ optional($note->delivery_date ?? $note->deliverySchedule?->scheduled_date)->format('d M Y') }}</strong>
                </div>
            </div>
        </div>

        <div class="grid">
            <div class="box">
                <h3>Dari (Supplier Raw Material)</h3>
                <p><strong>{{ $note->purchaseOrder->supplierRm->name }}</strong></p>
                <p>{{ $note->purchaseOrder->supplierRm->code }}</p>
                <p>{{ $note->purchaseOrder->supplierRm->address }}</p>
            </div>
            <div class="box">
                <h3>Tujuan (Supplier OH Part)</h3>
                <p><strong>{{ $note->deliverySchedule->ohpSupplier->name }}</strong></p>
                <p>{{ $note->deliverySchedule->ohpSupplier->code }}</p>
                <p>{{ $note->deliverySchedule->ohpSupplier->address }}</p>
            </div>
        </div>

        <div class="grid">
            <div class="box">
                <h3>Referensi PO</h3>
                <p><strong>{{ $note->purchaseOrder->po_number }}</strong></p>
                <p>Due Date: {{ $note->purchaseOrder->due_date->format('d M Y') }}</p>
            </div>
            <div class="box">
                <h3>Tanggal Pengiriman</h3>
                <p><strong>{{ optional($note->delivery_date ?? $note->deliverySchedule?->scheduled_date)->format('d M Y') }}</strong></p>
                <p>Plan: {{ optional($note->deliverySchedule?->scheduled_date)->format('d M Y') }}</p>
                <p>SJ Internal RM: <strong>{{ $note->rm_sj_number ?? $note->deliverySchedule->rm_sj_number ?? '—' }}</strong></p>
                <p>Final destination: PT. SDI (setelah proses OHP)</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Item Number</th>
                    <th>Deskripsi</th>
                    <th>Qty</th>
                    <th>UOM</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $note->purchaseOrderItem->item->item_number }}</td>
                    <td>{{ $note->purchaseOrderItem->item->description }}</td>
                    <td>{{ (int) round((float) $note->qty) }}</td>
                    <td>{{ $note->purchaseOrderItem->item->uom }}</td>
                </tr>
            </tbody>
        </table>

        <div class="barcode">
            *{{ $note->dn_number }}*
        </div>

        <div class="footer">
            <div class="sign">
                <div>Dibuat oleh</div>
                <div class="line">Purchasing SDI</div>
            </div>
            <div class="sign">
                <div>Dikirim oleh</div>
                <div class="line">Supplier RM</div>
            </div>
            <div class="sign">
                <div>Diterima oleh</div>
                <div class="line">Supplier OHP</div>
            </div>
        </div>
    </div>
</body>
</html>
