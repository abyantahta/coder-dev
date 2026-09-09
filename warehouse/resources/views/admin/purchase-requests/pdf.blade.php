<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Purchase Request {{ $purchaseRequest->pr_no }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h2 { margin: 0 0 4px; font-size: 16px; }
        .header p { margin: 0; font-size: 11px; color: #666; }
        .info-table { width: 100%; margin-bottom: 16px; border-collapse: collapse; }
        .info-table td { padding: 3px 6px; font-size: 11px; }
        .info-table td:first-child { font-weight: bold; width: 130px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table.items th { background: #f0f0f0; padding: 6px 8px; text-align: left; border: 1px solid #ddd; font-size: 11px; }
        table.items td { padding: 5px 8px; border: 1px solid #ddd; font-size: 11px; }
        table.items tr:nth-child(even) td { background: #f9f9f9; }
        .badge-status { padding: 2px 8px; border-radius: 4px; font-size: 10px; }
        .footer { margin-top: 30px; }
        .signature-box { display: inline-block; text-align: center; width: 180px; }
        .signature-line { border-top: 1px solid #333; margin-top: 50px; padding-top: 4px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>PURCHASE REQUEST</h2>
        <p>{{ config('app.name') }}</p>
    </div>

    <table class="info-table">
        <tr><td>No. PR</td><td>: <strong>{{ $purchaseRequest->pr_no }}</strong></td><td>Tanggal</td><td>: {{ $purchaseRequest->created_at->format('d/m/Y') }}</td></tr>
        <tr><td>Dibuat Oleh</td><td>: {{ $purchaseRequest->creator->name }}</td><td>Status</td><td>: {{ $purchaseRequest->getStatusLabel() }}</td></tr>
        <tr><td>Catatan</td><td colspan="3">: {{ $purchaseRequest->notes ?? '-' }}</td></tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="15%">Kode Item</th>
                <th>Deskripsi</th>
                <th width="8%">Satuan</th>
                <th width="12%">Stok Saat Ini</th>
                <th width="12%">Stok Minimum</th>
                <th width="12%">Qty Dibutuhkan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseRequest->details as $i => $detail)
            @php $resolved = $detail->resolvedItem(); @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $resolved->code }}</td>
                <td>{{ $resolved->name }}</td>
                <td>{{ $resolved->uom }}</td>
                <td style="text-align:right;color:#dc3545;">{{ $purchaseRequest->source === 'min_stock' ? number_format($detail->current_stock, 2) : '-' }}</td>
                <td style="text-align:right;">{{ $purchaseRequest->source === 'min_stock' ? number_format($detail->min_stock, 2) : '-' }}</td>
                <td style="text-align:right;font-weight:bold;">{{ number_format($detail->qty_needed, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <table style="width:100%;">
            <tr>
                <td style="width:33%; text-align:center;">
                    <div class="signature-box">
                        <div class="signature-line">Dibuat Oleh<br>{{ $purchaseRequest->creator->name }}</div>
                    </div>
                </td>
                <td style="width:33%; text-align:center;">
                    <div class="signature-box">
                        <div class="signature-line">Diketahui<br>Admin Warehouse</div>
                    </div>
                </td>
                <td style="width:33%; text-align:center;">
                    <div class="signature-box">
                        <div class="signature-line">Disetujui<br>Purchasing</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
