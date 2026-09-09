@extends('layouts.app')
@section('title', 'Masuk Barang')
@section('page-title', 'Transaksi Masuk Barang')
@section('breadcrumb')
    <li class="breadcrumb-item">Transaksi</li>
    <li class="breadcrumb-item active">Masuk Barang</li>
@endsection
@section('page-actions')
    @if(auth()->user()->isAdmin())
    <a href="{{ route('transactions.goods-in.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Input dari QAD Receipt
    </a>
    @endif
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="trxTable">
                <thead class="table-light">
                    <tr>
                        <th>No. Transaksi</th>
                        <th>No. Receipt QAD</th>
                        <th>Tanggal</th>
                        <th>User</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $trx->trans_no }}</span></td>
                        <td><span class="badge bg-info bg-opacity-10 text-info">{{ $trx->qad_receipt_no ?? '-' }}</span></td>
                        <td>{{ $trx->trans_date->format('d/m/Y') }}</td>
                        <td>{{ $trx->user->name }}</td>
                        <td><span class="badge bg-secondary">{{ $trx->details->count() }} item</span></td>
                        <td><span class="badge bg-{{ $trx->getStatusBadge() }}">{{ $trx->getStatusLabel() }}</span></td>
                        <td>
                            <a href="{{ route('admin.approvals.show', $trx->id) }}" class="btn btn-sm btn-light">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada transaksi masuk</td></tr>
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
    columnDefs: [{ orderable: false, targets: [6] }]
});
@endif
</script>
@endpush
