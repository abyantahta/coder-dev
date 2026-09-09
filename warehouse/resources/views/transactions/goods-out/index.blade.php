@extends('layouts.app')
@section('title', 'Keluar Barang')
@section('page-title', 'Transaksi Keluar Barang')
@section('breadcrumb')
    <li class="breadcrumb-item">Transaksi</li>
    <li class="breadcrumb-item active">Keluar Barang</li>
@endsection
@section('page-actions')
    <a href="{{ route('transactions.goods-out.scan') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-qr-code-scan me-1"></i>Buat Transaksi
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="trxTable">
                <thead class="table-light">
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>Departemen</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Disetujui Oleh</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $trx->trans_no }}</span></td>
                        <td>{{ $trx->trans_date->format('d/m/Y') }}</td>
                        <td>{{ $trx->user->name }}</td>
                        <td>{{ $trx->department?->name ?? '-' }}</td>
                        <td><span class="badge bg-secondary">{{ $trx->details->count() }} item</span></td>
                        <td>
                            <span class="badge bg-{{ $trx->getStatusBadge() }}">{{ $trx->getStatusLabel() }}</span>
                        </td>
                        <td>{{ $trx->approver?->name ?? '-' }}</td>
                        <td>
                            <a href="{{ route('transactions.goods-out.show', $trx->id) }}" class="btn btn-sm btn-light">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi keluar</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $transactions->links('pagination::bootstrap-5') }}</div>
@endsection

@push('scripts')
<script>
@if($transactions->count())
$('#trxTable').DataTable({
    paging: false, info: false,
    language: { search: 'Cari:', zeroRecords: 'Tidak ada data' },
    columnDefs: [{ orderable: false, targets: [7] }]
});
@endif
</script>
@endpush
