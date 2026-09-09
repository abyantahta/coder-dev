@extends('layouts.app')
@section('title','Terima Barang')
@section('page-title','Terima & Distribusi Barang')
@section('breadcrumb')
    <li class="breadcrumb-item">General Affair</li>
    <li class="breadcrumb-item active">Terima Barang</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <i class="bi bi-box-seam-fill me-2 text-primary"></i>PR Siap Diterima (sudah dikirim ke QAD)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>No. PR</th><th>No. Req QAD</th><th>Dibuat Oleh</th><th>Tanggal Kirim</th><th>Status</th><th width="120">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse($purchaseRequests as $pr)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $pr->pr_no }}</span></td>
                        <td>{{ $pr->qad_req_no ?? '-' }}</td>
                        <td>{{ $pr->creator->name }}</td>
                        <td>{{ $pr->sent_to_qad_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td><span class="badge bg-{{ $pr->getStatusBadge() }}">{{ $pr->getStatusLabel() }}</span></td>
                        <td>
                            <a href="{{ route('ga.receiving.create', $pr->id) }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-box-arrow-in-down me-1"></i>Terima
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada PR yang menunggu diterima</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Penerimaan</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="30"></th>
                        <th>No. PR</th>
                        <th>No. PO (QAD)</th>
                        <th>Diterima Oleh</th>
                        <th>Tanggal Terima</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($history as $pr)
                    @php $recvTotal = $pr->details->sum(fn($d) => $d->qty_needed * ($d->procurementItem->price ?? 0)); @endphp
                    <tr>
                        <td>
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#recv-items-{{ $pr->id }}">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </td>
                        <td><a href="{{ route('ga.requests.show', $pr->id) }}" class="fw-semibold text-primary text-decoration-none">{{ $pr->pr_no }}</a></td>
                        <td>{{ $pr->qad_po_no ?? '-' }}</td>
                        <td>{{ $pr->receivedBy?->name ?? '-' }}</td>
                        <td>{{ $pr->received_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td><span class="badge bg-{{ $pr->getStatusBadge() }}">{{ $pr->getStatusLabel() }}</span></td>
                    </tr>
                    <tr class="collapse" id="recv-items-{{ $pr->id }}">
                        <td></td>
                        <td colspan="5" class="bg-light">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr><th>Item</th><th>Qty</th><th>UOM</th><th>Harga Satuan</th><th>Subtotal</th><th>Budget Bulan Ini</th><th>Terpakai</th><th>Sisa</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($pr->details as $d)
                                    @php $bi = $budgetInfo[$d->id] ?? null; @endphp
                                    <tr>
                                        <td>{{ $d->procurementItem->name }} <span class="text-muted">({{ $d->procurementItem->item_code }})</span></td>
                                        <td>{{ number_format($d->qty_needed, 2) }}</td>
                                        <td>{{ $d->procurementItem->uom->code }}</td>
                                        <td>Rp {{ number_format($d->procurementItem->price ?? 0, 0, ',', '.') }}</td>
                                        <td class="fw-semibold">Rp {{ number_format($d->qty_needed * ($d->procurementItem->price ?? 0), 0, ',', '.') }}</td>
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
                                        <td class="fw-bold text-success">Rp {{ number_format($recvTotal, 0, ',', '.') }}</td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat penerimaan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $history->links('pagination::bootstrap-5') }}</div>
@endsection
