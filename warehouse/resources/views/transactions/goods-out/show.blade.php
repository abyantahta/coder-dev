@extends('layouts.app')
@section('title', 'Detail Transaksi')
@section('page-title', 'Detail Keluar Barang')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transactions.goods-out.index') }}" class="text-decoration-none">Keluar Barang</a></li>
    <li class="breadcrumb-item active">{{ $transaction->trans_no }}</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2 text-primary"></i>Info Transaksi</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">No. Transaksi</td><td class="fw-bold text-primary">{{ $transaction->trans_no }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Tanggal</td><td>{{ $transaction->trans_date->format('d/m/Y') }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">User</td><td>{{ $transaction->user->name }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">NPK</td><td>{{ $transaction->user->npk }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Departemen</td><td>{{ $transaction->department?->name ?? '-' }}</td></tr>
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Status</td>
                        <td><span class="badge bg-{{ $transaction->getStatusBadge() }}">{{ $transaction->getStatusLabel() }}</span></td>
                    </tr>
                    @if($transaction->approver)
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Disetujui</td><td>{{ $transaction->approver->name }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Waktu</td><td>{{ $transaction->approved_at?->format('d/m/Y H:i') }}</td></tr>
                    @endif
                    @if($transaction->reject_reason)
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Alasan Tolak</td><td class="text-danger">{{ $transaction->reject_reason }}</td></tr>
                    @endif
                    @if($transaction->notes)
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Catatan</td><td>{{ $transaction->notes }}</td></tr>
                    @endif
                </table>
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
                            <th>Stok Saat Ini</th>
                            <th>Qty Diminta</th>
                            <th>Qty Disetujui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($transaction->details as $i => $detail)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $detail->item->description }}</div>
                                <div class="text-muted" style="font-size:12px;">{{ $detail->item->item_code }}</div>
                            </td>
                            <td>
                                {{ number_format($detail->item->getQtyOnHand(), 2) }} {{ $detail->item->unit }}
                            </td>
                            <td class="fw-semibold">{{ number_format($detail->qty_requested, 2) }} {{ $detail->item->unit }}</td>
                            <td>
                                @if($detail->qty_approved > 0)
                                <span class="text-success fw-semibold">{{ number_format($detail->qty_approved, 2) }} {{ $detail->item->unit }}</span>
                                @else
                                <span class="text-muted">-</span>
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
</div>
@endsection
