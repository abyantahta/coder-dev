@extends('layouts.app')
@section('title', 'Detail Transaksi')
@section('page-title', $transaction->trans_no)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transactions.my-transactions.index') }}" class="text-decoration-none">Transaksi Saya</a></li>
    <li class="breadcrumb-item active">{{ $transaction->trans_no }}</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>Info Transaksi</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">No. Transaksi</td>
                        <td class="fw-bold text-primary">{{ $transaction->trans_no }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Tipe</td>
                        <td>
                            <span class="badge bg-{{ $transaction->getTypeBadgeColor() }}">
                                {{ $transaction->getTypeLabel() }}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Tanggal</td>
                        <td>{{ $transaction->trans_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Departemen</td>
                        <td>{{ $transaction->department?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Status</td>
                        <td><span class="badge bg-{{ $transaction->getStatusBadge() }}">{{ $transaction->getStatusLabel() }}</span></td>
                    </tr>
                    @if($transaction->notes)
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Catatan</td>
                        <td>{{ $transaction->notes }}</td>
                    </tr>
                    @endif
                </table>

                <div class="mt-3 d-grid gap-2">
                    @if($transaction->trans_type === 'goods_out')
                    <a href="{{ route('transactions.goods-return.scan') }}" class="btn btn-info btn-sm text-white">
                        <i class="bi bi-arrow-return-left me-1"></i>Kembalikan Sisa Barang
                    </a>
                    @endif
                    <a href="{{ route('transactions.my-transactions.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-box-seam me-2 text-primary"></i>Detail Item</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaction->details as $i => $detail)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $detail->item->description }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $detail->item->item_code }}</div>
                            </td>
                            <td class="fw-bold">{{ number_format($detail->qty_approved, 2) }} {{ $detail->item->unit }}</td>
                            <td class="text-muted" style="font-size:12px;">{{ $detail->notes ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
