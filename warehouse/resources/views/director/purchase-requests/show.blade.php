@extends('layouts.app')
@section('title','Detail PR')
@section('page-title', 'PR: ' . $purchaseRequest->pr_no)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('director.purchase-requests.index') }}" class="text-decoration-none">Approval PR</a></li>
    <li class="breadcrumb-item active">{{ $purchaseRequest->pr_no }}</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-earmark-text me-2 text-primary"></i>Info PR</span>
                <span class="badge bg-{{ $purchaseRequest->getStatusBadge() }}">{{ $purchaseRequest->getStatusLabel() }}</span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;width:110px;">No. PR</td><td class="fw-bold text-primary">{{ $purchaseRequest->pr_no }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Departemen</td><td>{{ $purchaseRequest->department?->name ?? '-' }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Diajukan GA</td><td>{{ $purchaseRequest->creator->name }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Tanggal</td><td>{{ $purchaseRequest->created_at->format('d/m/Y H:i') }}</td></tr>
                    @if($purchaseRequest->notes)
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Catatan GA</td><td>{{ $purchaseRequest->notes }}</td></tr>
                    @endif
                    @if($purchaseRequest->qad_req_no)
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">No. Req QAD</td><td class="fw-bold">{{ $purchaseRequest->qad_req_no }}</td></tr>
                    @endif
                </table>

                @if($purchaseRequest->qad_sync_message)
                <div class="alert alert-warning py-2 mb-3" style="font-size:12px;">
                    <i class="bi bi-info-circle me-1"></i>{{ $purchaseRequest->qad_sync_message }}
                </div>
                @endif

                @if($purchaseRequest->status === 'director_denied')
                <div class="alert alert-danger py-2 mb-3">
                    <div class="fw-semibold" style="font-size:13px;"><i class="bi bi-x-circle me-1"></i>Ditolak</div>
                    <div style="font-size:12px;">{{ $purchaseRequest->reject_reason }}</div>
                </div>
                @endif

                @if($purchaseRequest->status === 'sent_to_qad')
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success" onclick="showApproveModal({{ $purchaseRequest->id }},'{{ $purchaseRequest->pr_no }}')">
                        <i class="bi bi-check-lg me-1"></i>Setujui &amp; Kirim ke QAD
                    </button>
                    <button type="button" class="btn btn-outline-danger" onclick="showRejectModal({{ $purchaseRequest->id }},'{{ $purchaseRequest->pr_no }}')">
                        <i class="bi bi-x-lg me-1"></i>Tolak &amp; Kirim ke QAD
                    </button>
                </div>
                @endif
            </div>
        </div>

        @if($attachments->count())
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-paperclip me-2 text-primary"></i>Lampiran ({{ $attachments->count() }})</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($attachments as $att)
                    <li class="list-group-item d-flex align-items-center gap-2" style="font-size:13px;">
                        <i class="bi {{ $att->getIconClass() }} fs-5"></i>
                        <a href="javascript:void(0)" onclick="previewAttachment({{ Js::from(asset('storage/'.$att->file_path)) }}, {{ Js::from($att->original_name) }}, {{ Js::from($att->mime_type) }})" class="text-truncate flex-grow-1 text-decoration-none">
                            {{ $att->original_name }}
                        </a>
                        <span class="text-muted" style="font-size:11px;">{{ $att->getFormattedSize() }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-ul me-2 text-primary"></i>Item ({{ $purchaseRequest->details->count() }})</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>Item</th><th>Qty</th><th>Breakdown Departemen</th></tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseRequest->details as $i => $detail)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $detail->procurementItem->name }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $detail->procurementItem->item_code }}</div>
                            </td>
                            <td class="fw-bold text-primary">{{ number_format($detail->qty_needed, 2) }} {{ $detail->procurementItem->uom->code }}</td>
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
        </div>

        @if(!empty($budgetHistory))
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-graph-up me-2 text-primary"></i>Budget vs Actual — 12 Bulan Terakhir</div>
            <div class="card-body">
                <div style="position:relative;height:280px;">
                    <canvas id="budgetChart"></canvas>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Approve Modal --}}
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-success"><i class="bi bi-check-circle me-2"></i>Setujui PR</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <label class="form-label fw-semibold">Catatan (opsional)</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Setujui &amp; Kirim ke QAD</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Reject Modal --}}
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-danger"><i class="bi bi-x-circle me-2"></i>Tolak PR</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <p class="text-muted mb-3" style="font-size:13px;">PR akan ditolak dan dikirim ke QAD (Deny), lalu balik ke GA untuk direvisi.</p>
                    <label class="form-label fw-semibold">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak &amp; Kirim ke QAD</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function showApproveModal(id, no) {
    document.getElementById('approveForm').action = `/director/purchase-requests/${id}/approve`;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}
function showRejectModal(id, no) {
    document.getElementById('rejectForm').action = `/director/purchase-requests/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

@if(!empty($budgetHistory))
const budgetChartEl = document.getElementById('budgetChart');
if (budgetChartEl) {
    const rupiah = (v) => 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    new Chart(budgetChartEl, {
        type: 'bar',
        data: {
            labels: @json(collect($budgetHistory)->pluck('label')),
            datasets: [
                {
                    type: 'line',
                    label: 'Budget',
                    data: @json(collect($budgetHistory)->pluck('budget')),
                    borderColor: '#2a78d6',
                    backgroundColor: '#2a78d6',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#2a78d6',
                    tension: 0,
                    fill: false,
                    order: 0,
                },
                {
                    type: 'bar',
                    label: 'Actual',
                    data: @json(collect($budgetHistory)->pluck('consumed')),
                    backgroundColor: '#eb6834',
                    borderRadius: 4,
                    maxBarThickness: 28,
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, boxHeight: 12, font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: (ctx) => `${ctx.dataset.label}: ${rupiah(ctx.parsed.y)}`,
                    },
                },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#898781', font: { size: 11 } } },
                y: {
                    grid: { color: '#e1e0d9' },
                    ticks: { color: '#898781', font: { size: 11 }, callback: (v) => rupiah(v) },
                },
            },
        },
    });
}
@endif
</script>
@endpush
