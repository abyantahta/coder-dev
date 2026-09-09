@extends('layouts.app')
@section('title', 'Transaksi Saya')
@section('page-title', 'Transaksi Saya')
@section('breadcrumb')
    <li class="breadcrumb-item active">Transaksi Saya</li>
@endsection
@section('page-actions')
    <a href="{{ route('transactions.goods-out.scan') }}" class="btn btn-danger btn-sm me-1">
        <i class="bi bi-box-arrow-up-right me-1"></i>Ambil Barang
    </a>
    <a href="{{ route('transactions.goods-return.scan') }}" class="btn btn-info btn-sm text-white">
        <i class="bi bi-arrow-return-left me-1"></i>Kembalikan Barang
    </a>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="myTrxTable">
                <thead class="table-light">
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Tipe</th>
                        <th>Tanggal</th>
                        <th>Item</th>
                        <th>Catatan</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $trx)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $trx->trans_no }}</span></td>
                        <td>
                            <span class="badge bg-{{ $trx->getTypeBadgeColor() }} bg-opacity-15 text-{{ $trx->getTypeBadgeColor() }}">
                                @php
                                    $icon = match($trx->trans_type) {
                                        'goods_out'    => 'bi-box-arrow-up-right',
                                        'goods_return' => 'bi-arrow-return-left',
                                        default        => 'bi-arrow-left-right',
                                    };
                                @endphp
                                <i class="bi {{ $icon }} me-1"></i>{{ $trx->getTypeLabel() }}
                            </span>
                        </td>
                        <td>{{ $trx->trans_date->format('d/m/Y') }}</td>
                        <td>
                            @foreach($trx->details->take(2) as $d)
                                <div style="font-size:12px;">
                                    {{ $d->item->description }}:
                                    <strong>{{ number_format($d->qty_approved, 0) }} {{ $d->item->unit }}</strong>
                                </div>
                            @endforeach
                            @if($trx->details->count() > 2)
                                <div class="text-muted" style="font-size:11px;">+{{ $trx->details->count() - 2 }} item lainnya</div>
                            @endif
                        </td>
                        <td class="text-muted" style="font-size:12px;">{{ Str::limit($trx->notes, 35) ?: '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $trx->getStatusBadge() }}">{{ $trx->getStatusLabel() }}</span>
                        </td>
                        <td>
                            <a href="{{ route('transactions.my-transactions.show', $trx->id) }}"
                               class="btn btn-sm btn-light"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size:32px;"></i>
                            <p class="mt-2 mb-3">Belum ada transaksi</p>
                            <a href="{{ route('transactions.goods-out.scan') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-qr-code-scan me-1"></i>Mulai Ambil Barang
                            </a>
                        </td>
                    </tr>
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
$('#myTrxTable').DataTable({
    paging: false, info: false,
    language: { search: 'Cari:', zeroRecords: 'Tidak ada data' },
    columnDefs: [{ orderable: false, targets: [6] }]
});
@endif
</script>
@endpush
