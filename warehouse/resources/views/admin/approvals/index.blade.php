@extends('layouts.app')
@section('title', 'Approval Transaksi')
@section('page-title', 'Approval Transaksi')
@section('breadcrumb')
    <li class="breadcrumb-item">Admin</li>
    <li class="breadcrumb-item active">Approval</li>
@endsection

@section('content')

@if($pending->count())
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-clock-history fs-5"></i>
    <strong>{{ $pending->count() }} transaksi</strong>&nbsp;menunggu persetujuan Anda.
</div>
@endif

{{-- Pending Approvals --}}
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-clock me-2 text-warning"></i>Menunggu Approval</span>
        <span class="badge bg-warning text-dark">{{ $pending->count() }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Tipe</th>
                        <th>User / Dept</th>
                        <th>Tanggal</th>
                        <th>Item</th>
                        <th>Catatan</th>
                        <th width="140">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pending as $trx)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $trx->trans_no }}</span></td>
                        <td>
                            <span class="badge bg-{{ $trx->trans_type === 'goods_out' ? 'danger' : 'success' }} bg-opacity-10
                                text-{{ $trx->trans_type === 'goods_out' ? 'danger' : 'success' }}">
                                {{ $trx->getTypeLabel() }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold" style="font-size:13px;">{{ $trx->user->name }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $trx->department?->name ?? '-' }}</div>
                        </td>
                        <td>{{ $trx->trans_date->format('d/m/Y') }}</td>
                        <td>
                            @foreach($trx->details->take(2) as $d)
                            <div style="font-size:12px;">{{ $d->item->description }}: <strong>{{ $d->qty_requested }} {{ $d->item->unit }}</strong></div>
                            @endforeach
                            @if($trx->details->count() > 2)
                            <div class="text-muted" style="font-size:11px;">+{{ $trx->details->count() - 2 }} lainnya</div>
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:12px;">{{ Str::limit($trx->notes, 40) ?? '-' }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.approvals.show', $trx->id) }}"
                                   class="btn btn-sm btn-primary" title="Review">
                                    <i class="bi bi-check2-circle me-1"></i>Review
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-check-circle text-success" style="font-size:28px;"></i>
                        <p class="mt-2 mb-0">Tidak ada transaksi yang menunggu approval</p>
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- History --}}
<div class="card">
    <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Approval</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="historyTable">
                <thead class="table-light">
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Tipe</th>
                        <th>User</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Diproses Oleh</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $trx)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $trx->trans_no }}</span></td>
                        <td>{{ $trx->getTypeLabel() }}</td>
                        <td>{{ $trx->user->name }}</td>
                        <td>{{ $trx->trans_date->format('d/m/Y') }}</td>
                        <td><span class="badge bg-{{ $trx->getStatusBadge() }}">{{ $trx->getStatusLabel() }}</span></td>
                        <td>{{ $trx->approver?->name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('admin.approvals.show', $trx->id) }}" class="btn btn-sm btn-light">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
@if($history->count())
$('#historyTable').DataTable({
    pageLength: 10,
    language: { search: 'Cari:', paginate: { previous: '&laquo;', next: '&raquo;' }, info: 'Menampilkan _START_-_END_ dari _TOTAL_', zeroRecords: 'Tidak ada data' }
});
@endif
</script>
@endpush
