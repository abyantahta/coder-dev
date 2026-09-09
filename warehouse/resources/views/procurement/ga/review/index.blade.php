@extends('layouts.app')
@section('title','Review Kebutuhan GA')
@section('page-title','Review & Agregasi Kebutuhan GA')
@section('breadcrumb')
    <li class="breadcrumb-item">General Affair</li>
    <li class="breadcrumb-item active">Review Kebutuhan</li>
@endsection

@section('content')

@if($requests->count())
<div class="alert alert-warning d-flex gap-2 align-items-center mb-4">
    <i class="bi bi-clock-history fs-5"></i>
    <div><strong>{{ $requests->count() }} permintaan</strong> dari <strong>{{ $byDepartment->count() }} departemen</strong> menunggu direview.</div>
</div>
@endif

<form id="bulkApproveForm" action="{{ route('ga.review.bulk-approve') }}" method="POST">
    @csrf

    <ul class="nav nav-tabs mb-3" id="gaReviewTabs" role="tablist">
        @foreach($byDepartment as $deptName => $deptRequests)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                    data-bs-target="#dept-{{ Str::slug($deptName) }}" type="button">
                {{ $deptName }} <span class="badge bg-secondary ms-1">{{ $deptRequests->count() }}</span>
            </button>
        </li>
        @endforeach
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $byDepartment->isEmpty() ? 'active' : '' }}" data-bs-toggle="tab"
                    data-bs-target="#summary-tab" type="button">
                <i class="bi bi-bar-chart-fill me-1"></i>Summary
            </button>
        </li>
    </ul>

    <div class="tab-content">
        @foreach($byDepartment as $deptName => $deptRequests)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="dept-{{ Str::slug($deptName) }}">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="30"><input type="checkbox" class="form-check-input dept-check-all" data-dept="{{ Str::slug($deptName) }}"></th>
                                    <th width="30"></th>
                                    <th>No. Permintaan</th>
                                    <th>Pemohon</th>
                                    <th>Keperluan</th>
                                    <th>Item</th>
                                    <th>Total</th>
                                    <th width="150">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($deptRequests as $req)
                                @php $reqTotal = $req->details->sum(fn($d) => $d->qty * ($d->item->price ?? 0)); @endphp
                                <tr>
                                    <td><input type="checkbox" class="form-check-input dept-check-{{ Str::slug($deptName) }}" name="request_ids[]" value="{{ $req->id }}"></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#items-{{ $req->id }}">
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                    </td>
                                    <td><span class="fw-semibold text-primary">{{ $req->req_no }}</span></td>
                                    <td>
                                        <div class="fw-semibold" style="font-size:13px;">{{ $req->user->name }}</div>
                                        <div class="text-muted" style="font-size:11px;">{{ $req->user->npk }}</div>
                                    </td>
                                    <td style="font-size:13px;">
                                        {{ Str::limit($req->purpose, 30) }}
                                        @if($req->attachments->count())
                                        <i class="bi bi-paperclip text-muted ms-1" title="{{ $req->attachments->count() }} lampiran"></i>
                                        @endif
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $req->details->count() }} item</span></td>
                                    <td class="fw-semibold text-success">Rp {{ number_format($reqTotal, 0, ',', '.') }}</td>
                                    <td>
                                        <button type="submit" class="btn btn-sm btn-success me-1" formaction="{{ route('ga.review.approve', $req->id) }}">
                                            <i class="bi bi-check-lg"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="showRejectModal({{ $req->id }},'{{ $req->req_no }}')">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="collapse" id="items-{{ $req->id }}">
                                    <td></td>
                                    <td colspan="7" class="bg-light">
                                        <table class="table table-sm mb-0">
                                            <thead><tr><th>Item</th><th>Qty</th><th>UOM</th><th>Harga Satuan</th><th>Subtotal</th><th>Budget Bulan Ini</th><th>Terpakai</th><th>Sisa</th><th>Catatan</th></tr></thead>
                                            <tbody>
                                                @foreach($req->details as $d)
                                                @php $bi = $budgetInfo[$d->id] ?? null; @endphp
                                                <tr>
                                                    <td>{{ $d->item->name }} <span class="text-muted">({{ $d->item->item_code }})</span></td>
                                                    <td>{{ number_format($d->qty, 2) }}</td>
                                                    <td>{{ $d->uom->code }}</td>
                                                    <td>Rp {{ number_format($d->item->price ?? 0, 0, ',', '.') }}</td>
                                                    <td class="fw-semibold">Rp {{ number_format($d->qty * ($d->item->price ?? 0), 0, ',', '.') }}</td>
                                                    @if($bi)
                                                    <td>Rp {{ number_format($bi['budget'], 0, ',', '.') }}</td>
                                                    <td>Rp {{ number_format($bi['consumed'], 0, ',', '.') }}</td>
                                                    <td class="fw-semibold {{ $bi['remaining'] < 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($bi['remaining'], 0, ',', '.') }}</td>
                                                    @else
                                                    <td colspan="3" class="text-muted">-</td>
                                                    @endif
                                                    <td class="text-muted">{{ $d->notes ?: '-' }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <td colspan="4" class="text-end fw-bold">Total</td>
                                                    <td class="fw-bold text-success">Rp {{ number_format($reqTotal, 0, ',', '.') }}</td>
                                                    <td colspan="4"></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                        @if($req->attachments->count())
                                        <div class="mt-2 pt-2 border-top">
                                            <div class="fw-semibold mb-1" style="font-size:12px;"><i class="bi bi-paperclip me-1"></i>Lampiran ({{ $req->attachments->count() }})</div>
                                            <div class="d-flex flex-wrap gap-2">
                                                @foreach($req->attachments as $att)
                                                <a href="javascript:void(0)" onclick="previewAttachment({{ Js::from(asset('storage/'.$att->file_path)) }}, {{ Js::from($att->original_name) }}, {{ Js::from($att->mime_type) }})"
                                                   class="d-flex align-items-center gap-1 px-2 py-1 bg-white border rounded text-decoration-none"
                                                   style="font-size:12px;" title="{{ $att->original_name }}">
                                                    <i class="bi {{ $att->getIconClass() }}"></i>
                                                    <span class="text-truncate" style="max-width:140px;">{{ $att->original_name }}</span>
                                                </a>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endforeach

        <div class="tab-pane fade {{ $byDepartment->isEmpty() ? 'show active' : '' }}" id="summary-tab">
            <div class="card">
                <div class="card-header"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Akumulasi Per Produk (Semua Departemen)</div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Item</th><th>Kategori</th><th>Total Qty Diminta</th><th>Jumlah Request</th><th>Harga Satuan</th><th>Subtotal</th></tr>
                            </thead>
                            <tbody>
                                @forelse($summary as $row)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $row->item->name }}</div>
                                        <div class="text-muted" style="font-size:11px;">{{ $row->item->item_code }}</div>
                                    </td>
                                    <td>{{ $row->item->category?->name ?? '-' }}</td>
                                    <td class="fw-bold text-primary">{{ number_format($row->qty, 2) }}</td>
                                    <td>{{ $row->count }}</td>
                                    <td>Rp {{ number_format($row->item->price ?? 0, 0, ',', '.') }}</td>
                                    <td class="fw-semibold text-success">Rp {{ number_format($row->qty * ($row->item->price ?? 0), 0, ',', '.') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada permintaan menunggu review</td></tr>
                                @endforelse
                            </tbody>
                            @if($summary->count())
                            <tfoot>
                                <tr>
                                    <td colspan="5" class="text-end fw-bold">Grand Total</td>
                                    <td class="fw-bold text-success">Rp {{ number_format($summary->sum(fn($row) => $row->qty * ($row->item->price ?? 0)), 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <label class="form-label fw-semibold">Catatan Agregasi (opsional)</label>
        <textarea name="notes" class="form-control mb-2" rows="2" placeholder="Catatan untuk PR gabungan ini..."></textarea>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Permintaan yang dicentang akan digabung jadi PR per departemen dan LANGSUNG dikirim ke QAD. Lanjutkan?')">
            <i class="bi bi-cloud-upload me-1"></i>Approve &amp; Kirim ke QAD
        </button>
    </div>
</form>

<div class="card mt-3">
    <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="30"></th>
                        <th>No. Permintaan</th>
                        <th>No. PR (QAD)</th>
                        <th>No. PO</th>
                        <th>Pemohon</th>
                        <th>Departemen</th>
                        <th>Keperluan</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $req)
                    @php $histTotal = $req->details->sum(fn($d) => $d->qty * ($d->item->price ?? 0)); @endphp
                    <tr>
                        <td>
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#hist-items-{{ $req->id }}">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </td>
                        <td><a href="{{ route('procurement.requests.show', $req->id) }}" class="fw-semibold text-primary text-decoration-none">{{ $req->req_no }}</a></td>
                        <td>{{ $req->aggregatedPr->qad_req_no ?? '-' }}</td>
                        <td>{{ $req->aggregatedPr->qad_po_no ?? '-' }}</td>
                        <td>{{ $req->user->name }}</td>
                        <td>{{ $req->department?->name ?? '-' }}</td>
                        <td>
                            {{ Str::limit($req->purpose, 30) }}
                            @if($req->attachments->count())
                            <i class="bi bi-paperclip text-muted ms-1" title="{{ $req->attachments->count() }} lampiran"></i>
                            @endif
                        </td>
                        <td>{{ $req->req_date->format('d/m/Y') }}</td>
                        <td><span class="badge bg-{{ $req->getStatusBadge() }}">{{ $req->getStatusLabel() }}</span></td>
                    </tr>
                    <tr class="collapse" id="hist-items-{{ $req->id }}">
                        <td></td>
                        <td colspan="8" class="bg-light">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr><th>Item</th><th>Qty</th><th>UOM</th><th>Harga Satuan</th><th>Subtotal</th><th>Budget Bulan Ini</th><th>Terpakai</th><th>Sisa</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($req->details as $d)
                                    @php $bi = $budgetInfo[$d->id] ?? null; @endphp
                                    <tr>
                                        <td>{{ $d->item->name }} <span class="text-muted">({{ $d->item->item_code }})</span></td>
                                        <td>{{ number_format($d->qty, 2) }}</td>
                                        <td>{{ $d->uom->code }}</td>
                                        <td>Rp {{ number_format($d->item->price ?? 0, 0, ',', '.') }}</td>
                                        <td class="fw-semibold">Rp {{ number_format($d->qty * ($d->item->price ?? 0), 0, ',', '.') }}</td>
                                        @if($bi)
                                        <td>Rp {{ number_format($bi['budget'], 0, ',', '.') }}</td>
                                        <td>Rp {{ number_format($bi['consumed'], 0, ',', '.') }}</td>
                                        <td class="fw-semibold {{ $bi['remaining'] < 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($bi['remaining'], 0, ',', '.') }}</td>
                                        @else
                                        <td colspan="3" class="text-muted">-</td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Total</td>
                                        <td class="fw-bold text-success">Rp {{ number_format($histTotal, 0, ',', '.') }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                            @if($req->attachments->count())
                            <div class="mt-2 pt-2 border-top">
                                <div class="fw-semibold mb-1" style="font-size:12px;"><i class="bi bi-paperclip me-1"></i>Lampiran ({{ $req->attachments->count() }})</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($req->attachments as $att)
                                    <a href="javascript:void(0)" onclick="previewAttachment({{ Js::from(asset('storage/'.$att->file_path)) }}, {{ Js::from($att->original_name) }}, {{ Js::from($att->mime_type) }})"
                                       class="d-flex align-items-center gap-1 px-2 py-1 bg-white border rounded text-decoration-none"
                                       style="font-size:12px;" title="{{ $att->original_name }}">
                                        <i class="bi {{ $att->getIconClass() }}"></i>
                                        <span class="text-truncate" style="max-width:140px;">{{ $att->original_name }}</span>
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada riwayat</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>Tolak Permintaan</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <p class="text-muted mb-3" style="font-size:13px;">Permintaan <strong id="rejectReqNo"></strong> akan ditolak.</p>
                    <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.dept-check-all').forEach(el => {
    el.addEventListener('change', function () {
        document.querySelectorAll('.dept-check-' + this.dataset.dept).forEach(c => c.checked = this.checked);
    });
});

function showRejectModal(id, no) {
    document.getElementById('rejectReqNo').textContent = no;
    document.getElementById('rejectForm').action = `/ga/review/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endpush
