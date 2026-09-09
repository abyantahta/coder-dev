@extends('layouts.app')
@section('title','Konfirmasi PR')
@section('page-title','Konfirmasi Purchase Request')
@section('breadcrumb')
    <li class="breadcrumb-item">Direktur</li>
    <li class="breadcrumb-item active">Konfirmasi PR</li>
@endsection

@section('content')

@if($pending->count())
<div class="alert alert-info d-flex gap-2 align-items-center mb-4">
    <i class="bi bi-info-circle fs-5"></i>
    <div><strong>{{ $pending->count() }} PR</strong> sudah terkirim ke QAD, menunggu approval Anda. Setujui/Tolak di sini akan langsung dikirim ke QAD.</div>
</div>
@endif

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-clock me-2 text-warning"></i>Menunggu Konfirmasi</span>
        <span class="badge bg-warning text-dark">{{ $pending->count() }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>No. PR</th><th>Departemen</th><th>Dibuat Oleh</th><th>Jumlah Item</th><th>Tanggal</th><th width="180">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($pending as $pr)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $pr->pr_no }}</span></td>
                        <td>{{ $pr->department?->name ?? '-' }}</td>
                        <td>{{ $pr->creator->name }}</td>
                        <td><span class="badge bg-secondary">{{ $pr->details->count() }} item</span></td>
                        <td>{{ $pr->created_at->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ route('director.purchase-requests.show', $pr->id) }}" class="btn btn-sm btn-light me-1">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button class="btn btn-sm btn-success me-1" onclick="showApproveModal({{ $pr->id }},'{{ $pr->pr_no }}')">
                                <i class="bi bi-check-lg"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="showRejectModal({{ $pr->id }},'{{ $pr->pr_no }}')">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada PR yang menunggu konfirmasi</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="historyTable">
                <thead class="table-light">
                    <tr><th>No. PR</th><th>Departemen</th><th>Dibuat Oleh</th><th>Tanggal</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @forelse($history as $pr)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $pr->pr_no }}</span></td>
                        <td>{{ $pr->department?->name ?? '-' }}</td>
                        <td>{{ $pr->creator->name }}</td>
                        <td>{{ $pr->created_at->format('d/m/Y') }}</td>
                        <td><span class="badge bg-{{ $pr->getStatusBadge() }}">{{ $pr->getStatusLabel() }}</span></td>
                        <td><a href="{{ route('director.purchase-requests.show', $pr->id) }}" class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada riwayat</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Approve Modal --}}
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0">
                <h6 class="modal-title fw-bold text-success"><i class="bi bi-check-circle me-2"></i>Konfirmasi PR</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveForm" method="POST">
                @csrf
                <div class="modal-body pt-0">
                    <p class="text-muted mb-3" style="font-size:13px;">PR <strong id="approveReqNo"></strong> akan disetujui dan dikirim ke QAD (Approval_PR).</p>
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
                    <p class="text-muted mb-3" style="font-size:13px;">PR <strong id="rejectReqNo"></strong> akan ditolak dan dikirim ke QAD (Deny) — akan balik ke GA untuk direvisi & dikirim ulang pakai nomor requisition yang sama.</p>
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
@if($history->count())
$('#historyTable').DataTable({ pageLength:10, language:{ search:'Cari:' } });
@endif

function showApproveModal(id, no) {
    document.getElementById('approveReqNo').textContent = no;
    document.getElementById('approveForm').action = `/director/purchase-requests/${id}/approve`;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}
function showRejectModal(id, no) {
    document.getElementById('rejectReqNo').textContent = no;
    document.getElementById('rejectForm').action = `/director/purchase-requests/${id}/reject`;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>
@endpush
