@extends('layouts.app')
@section('title', 'Review Approval')
@section('page-title', 'Review Transaksi: ' . $transaction->trans_no)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.approvals.index') }}" class="text-decoration-none">Approval</a></li>
    <li class="breadcrumb-item active">{{ $transaction->trans_no }}</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-person me-2 text-primary"></i>Info Pemohon</div>
            <div class="card-body">
                <div class="d-flex gap-3 align-items-center mb-3">
                    <img src="{{ $transaction->user->photo ? asset('storage/'.$transaction->user->photo) : asset('assets/images/avatar.png') }}"
                         class="rounded-circle" style="width:52px;height:52px;object-fit:cover;">
                    <div>
                        <div class="fw-bold">{{ $transaction->user->name }}</div>
                        <div class="text-muted" style="font-size:12px;">NPK: {{ $transaction->user->npk }}</div>
                        <div class="text-muted" style="font-size:12px;">{{ $transaction->department?->name ?? '-' }}</div>
                    </div>
                </div>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted" style="font-size:12px;font-weight:600;">No. Transaksi</td><td class="fw-bold text-primary">{{ $transaction->trans_no }}</td></tr>
                    <tr><td class="text-muted" style="font-size:12px;font-weight:600;">Tipe</td><td>{{ $transaction->getTypeLabel() }}</td></tr>
                    <tr><td class="text-muted" style="font-size:12px;font-weight:600;">Tanggal</td><td>{{ $transaction->trans_date->format('d/m/Y') }}</td></tr>
                    <tr>
                        <td class="text-muted" style="font-size:12px;font-weight:600;">Status</td>
                        <td><span class="badge bg-{{ $transaction->getStatusBadge() }}">{{ $transaction->getStatusLabel() }}</span></td>
                    </tr>
                    @if($transaction->notes)
                    <tr><td class="text-muted" style="font-size:12px;font-weight:600;">Catatan</td><td>{{ $transaction->notes }}</td></tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-box-seam me-2 text-primary"></i>Detail Item</div>

            @if($transaction->status === 'pending')
            <form action="{{ route('admin.approvals.approve', $transaction->id) }}" method="POST" id="approveForm">
                @csrf
            @endif

            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Stok Saat Ini</th>
                            <th>Qty Diminta</th>
                            @if($transaction->status === 'pending')
                            <th width="130">Qty Disetujui</th>
                            @else
                            <th>Qty Disetujui</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaction->details as $i => $detail)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="d-flex gap-2 align-items-center">
                                    <img src="{{ $detail->item->photo ? asset('storage/'.$detail->item->photo) : asset('assets/images/item-placeholder.png') }}"
                                         style="width:36px;height:36px;border-radius:6px;object-fit:cover;">
                                    <div>
                                        <div class="fw-semibold">{{ $detail->item->description }}</div>
                                        <div class="text-muted" style="font-size:11px;">{{ $detail->item->item_code }}
                                            @if($detail->item->is_memo)<span class="badge bg-info ms-1">Memo</span>@endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php $qtyOnHand = $detail->item->getQtyOnHand(); @endphp
                                <span class="{{ $qtyOnHand <= 0 ? 'text-danger fw-bold' : ($detail->item->isBelowMinimum() ? 'text-warning fw-semibold' : 'text-success') }}">
                                    {{ number_format($qtyOnHand, 2) }} {{ $detail->item->unit }}
                                </span>
                                @if($qtyOnHand <= 0)<div class="text-danger" style="font-size:10px;">HABIS</div>@endif
                            </td>
                            <td class="fw-semibold">{{ number_format($detail->qty_requested, 2) }} {{ $detail->item->unit }}</td>
                            <td>
                                @if($transaction->status === 'pending')
                                <input type="number" name="approved_quantities[{{ $detail->id }}]"
                                       class="form-control form-control-sm"
                                       value="{{ min($detail->qty_requested, $qtyOnHand) }}"
                                       max="{{ $qtyOnHand }}" min="0" step="0.01">
                                @else
                                <span class="{{ $detail->qty_approved > 0 ? 'text-success fw-semibold' : 'text-muted' }}">
                                    {{ number_format($detail->qty_approved, 2) }} {{ $detail->item->unit }}
                                </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>

            @if($transaction->status === 'pending')
            <div class="card-footer">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success" onclick="return confirmApprove()">
                        <i class="bi bi-check-circle me-1"></i>Setujui & Kurangi Stok
                    </button>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle me-1"></i>Tolak
                    </button>
                    <a href="{{ route('admin.approvals.index') }}" class="btn btn-outline-secondary ms-auto">
                        Kembali
                    </a>
                </div>
            </div>
            </form>
            @else
            <div class="card-footer">
                @if($transaction->reject_reason)
                <div class="alert alert-danger mb-2 py-2">
                    <i class="bi bi-x-circle me-1"></i><strong>Alasan Penolakan:</strong> {{ $transaction->reject_reason }}
                </div>
                @endif
                <a href="{{ route('admin.approvals.index') }}" class="btn btn-outline-secondary">Kembali</a>
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>Tolak Transaksi</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.approvals.reject', $transaction->id) }}" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Tuliskan alasan penolakan..." required></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmApprove() {
    return confirm('Setujui transaksi ini? Stok akan dikurangi sesuai qty yang disetujui.');
}
</script>
@endpush
