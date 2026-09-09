@extends('layouts.app')
@section('title','Terima Barang')
@section('page-title', 'Terima Barang: ' . $purchaseRequest->pr_no)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ga.receiving.index') }}" class="text-decoration-none">Terima Barang</a></li>
    <li class="breadcrumb-item active">{{ $purchaseRequest->pr_no }}</li>
@endsection

@section('content')
<form action="{{ route('ga.receiving.store', $purchaseRequest->id) }}" method="POST">
    @csrf

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label fw-semibold">No. PO (QAD) <span class="text-danger">*</span></label>
            @if($purchaseRequest->qad_po_no)
                <input type="text" class="form-control" value="{{ $purchaseRequest->qad_po_no }}" readonly>
                <input type="hidden" name="qad_po_no" value="{{ $purchaseRequest->qad_po_no }}">
                <div class="form-text text-success" style="font-size:11px;">Otomatis terdeteksi dari QAD.</div>
            @else
                <input type="text" name="qad_po_no" class="form-control @error('qad_po_no') is-invalid @enderror"
                       value="{{ old('qad_po_no') }}" placeholder="PO026752" required>
                @error('qad_po_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text text-warning" style="font-size:11px;">Belum terdeteksi otomatis dari QAD — isi manual dengan hati-hati, pastikan PO ini benar milik PR ini.</div>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">No. Receipt QAD (opsional)</label>
            <input type="text" name="qad_receipt_no" class="form-control" placeholder="RCV-xxxx">
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold">Catatan</label>
            <input type="text" name="notes" class="form-control" value="{{ old('notes', $defaultNotes) }}" placeholder="Catatan penerimaan...">
            <div class="form-text" style="font-size:11px;">Otomatis terisi sesuai departemen — bisa diedit.</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="bi bi-list-ul me-2 text-primary"></i>Konfirmasi Qty Diterima</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item</th>
                            <th>Diminta</th>
                            <th>Sudah Diterima</th>
                            <th width="150">Qty Diterima (sekarang)</th>
                            <th>Breakdown Departemen (proporsional)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseRequest->details as $detail)
                        @php
                            $alreadyReceived = $receivedByDetailId[$detail->id] ?? 0;
                            $remaining = max(0, $detail->qty_needed - $alreadyReceived);
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $detail->procurementItem->name }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $detail->procurementItem->item_code }}</div>
                                <input type="hidden" name="items[{{ $loop->index }}][detail_id]" value="{{ $detail->id }}">
                            </td>
                            <td>{{ number_format($detail->qty_needed, 2) }} {{ $detail->procurementItem->uom->code }}</td>
                            <td>
                                @if($alreadyReceived > 0)
                                    <span class="text-success fw-semibold">{{ number_format($alreadyReceived, 2) }}</span>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" max="{{ $remaining }}"
                                       name="items[{{ $loop->index }}][qty_received]"
                                       class="form-control form-control-sm"
                                       value="{{ $remaining }}">
                                @if($alreadyReceived > 0)
                                    <div class="form-text text-warning" style="font-size:10px;">Sisa: {{ number_format($remaining, 2) }}</div>
                                @endif
                            </td>
                            <td style="font-size:11px;">
                                @foreach($detail->sources as $src)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary me-1 mb-1">
                                        {{ $src->department->name }}: {{ number_format($src->qty, 2) }}
                                    </span>
                                @endforeach
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-transparent d-flex gap-2">
            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Konfirmasi penerimaan barang dan alokasikan ke departemen?')">
                <i class="bi bi-check-lg me-1"></i>Konfirmasi Terima &amp; Distribusikan
            </button>
            <a href="{{ route('ga.receiving.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </div>
</form>
@endsection
