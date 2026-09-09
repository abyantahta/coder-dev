@extends('layouts.app')
@section('title','Revisi PR')
@section('page-title', 'Revisi PR: ' . $purchaseRequest->pr_no)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ga.requests.show', $purchaseRequest->id) }}" class="text-decoration-none">{{ $purchaseRequest->pr_no }}</a></li>
    <li class="breadcrumb-item active">Revisi</li>
@endsection

@section('content')
<div class="alert alert-warning" style="font-size:13px;">
    <i class="bi bi-info-circle me-1"></i>
    Ditolak Direktur dengan alasan: <strong>{{ $purchaseRequest->reject_reason }}</strong><br>
    Ubah qty di bawah lalu kirim ulang — nomor requisition QAD <strong>{{ $purchaseRequest->qad_req_no }}</strong> akan tetap dipakai (di-update, bukan bikin baru).
</div>

<form action="{{ route('ga.requests.revise.submit', $purchaseRequest->id) }}" method="POST">
    @csrf
    <div class="card">
        <div class="card-header"><i class="bi bi-pencil-square me-2 text-primary"></i>Revisi Qty</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Item</th><th width="150">Qty</th></tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseRequest->details as $detail)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $detail->procurementItem->name }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $detail->procurementItem->item_code }}</div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input type="number" step="0.01" min="0.01" name="qty[{{ $detail->id }}]"
                                           class="form-control" value="{{ $detail->qty_needed }}" required>
                                    <span class="input-group-text">{{ $detail->procurementItem->uom->code }}</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <label class="form-label fw-semibold" style="font-size:12px;">Catatan Revisi (opsional)</label>
            <textarea name="notes" class="form-control mb-3" rows="2" placeholder="Catatan perubahan...">{{ $purchaseRequest->notes }}</textarea>
            <button type="submit" class="btn btn-primary" onclick="return confirm('Kirim ulang PR ini ke QAD dengan qty yang direvisi?')">
                <i class="bi bi-send me-1"></i>Kirim Ulang ke QAD
            </button>
            <a href="{{ route('ga.requests.show', $purchaseRequest->id) }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </div>
</form>
@endsection
