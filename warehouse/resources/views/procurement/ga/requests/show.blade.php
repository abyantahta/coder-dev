@extends('layouts.app')
@section('title','Detail PR')
@section('page-title', 'PR: ' . $purchaseRequest->pr_no)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ga.requests.index') }}" class="text-decoration-none">Riwayat PR</a></li>
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
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Diajukan Oleh</td><td>{{ $purchaseRequest->creator->name }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Tanggal</td><td>{{ $purchaseRequest->created_at->format('d/m/Y H:i') }}</td></tr>
                    @if($purchaseRequest->qad_req_no)
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">No. Req QAD</td><td class="fw-bold">{{ $purchaseRequest->qad_req_no }}</td></tr>
                    @endif
                    @if($purchaseRequest->qad_po_no)
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">No. PO</td><td class="fw-bold text-success">{{ $purchaseRequest->qad_po_no }}</td></tr>
                    @endif
                    @if($purchaseRequest->notes)
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Catatan</td><td>{{ $purchaseRequest->notes }}</td></tr>
                    @endif
                </table>

                @if($purchaseRequest->status === 'send_failed')
                <div class="alert alert-danger py-2 mb-3">
                    <div class="fw-semibold" style="font-size:13px;"><i class="bi bi-x-circle me-1"></i>Gagal Kirim ke QAD</div>
                    <div style="font-size:12px;">{{ $purchaseRequest->qad_sync_message }}</div>
                </div>
                <form action="{{ route('ga.requests.retry-send', $purchaseRequest->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-arrow-repeat me-1"></i>Coba Kirim Ulang</button>
                </form>
                @elseif($purchaseRequest->qad_sync_message)
                <div class="alert alert-warning py-2 mb-3" style="font-size:12px;">
                    <i class="bi bi-info-circle me-1"></i>{{ $purchaseRequest->qad_sync_message }}
                </div>
                @endif

                @if($purchaseRequest->status === 'director_denied')
                <div class="alert alert-danger py-2 mb-3">
                    <div class="fw-semibold" style="font-size:13px;"><i class="bi bi-x-circle me-1"></i>Ditolak Direktur</div>
                    <div style="font-size:12px;">{{ $purchaseRequest->reject_reason }}</div>
                </div>
                <a href="{{ route('ga.requests.revise', $purchaseRequest->id) }}" class="btn btn-primary w-100">
                    <i class="bi bi-pencil-square me-1"></i>Revisi &amp; Kirim Ulang
                </a>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-diagram-3 me-2 text-primary"></i>Status Perjalanan</div>
            <div class="card-body">
                @php
                    $steps = [
                        ['key'=>'sent_to_qad','label'=>'Terkirim ke QAD'.($purchaseRequest->qad_req_no ? ' (No: '.$purchaseRequest->qad_req_no.')' : ''),'done'=>in_array($purchaseRequest->status,['sent_to_qad','director_confirmed','received','distributing','completed'])],
                        ['key'=>'director_confirmed','label'=>'Disetujui Direktur — '.($purchaseRequest->directorBy?->name ?? '-'),'done'=>$purchaseRequest->status==='director_confirmed' || !is_null($purchaseRequest->received_at)],
                        ['key'=>'received','label'=>'Barang Diterima GA','done'=>!is_null($purchaseRequest->received_at)],
                        ['key'=>'completed','label'=>'Selesai (semua sudah diambil)','done'=>$purchaseRequest->status==='completed'],
                    ];
                @endphp
                <ul class="list-unstyled mb-0">
                    @if($purchaseRequest->status === 'send_failed')
                    <li class="d-flex align-items-center gap-2 mb-2" style="font-size:13px;">
                        <i class="bi bi-x-circle-fill text-danger"></i>
                        <span class="text-danger">Gagal terkirim ke QAD</span>
                    </li>
                    @else
                    <li class="d-flex align-items-center gap-2 mb-2" style="font-size:13px;">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <span>Terkirim ke QAD{{ $purchaseRequest->qad_req_no ? ' (No: '.$purchaseRequest->qad_req_no.')' : '' }}</span>
                    </li>
                    @if($purchaseRequest->status === 'director_denied')
                    <li class="d-flex align-items-center gap-2 mb-2" style="font-size:13px;">
                        <i class="bi bi-x-circle-fill text-danger"></i>
                        <span class="text-danger">Ditolak Direktur — {{ $purchaseRequest->directorBy?->name ?? '-' }}</span>
                    </li>
                    @else
                    @foreach(array_slice($steps, 1) as $step)
                    <li class="d-flex align-items-center gap-2 mb-2" style="font-size:13px;">
                        <i class="bi {{ $step['done'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted' }}"></i>
                        <span class="{{ $step['done'] ? '' : 'text-muted' }}">{{ $step['label'] }}</span>
                    </li>
                    @endforeach
                    @endif
                    @endif
                </ul>
            </div>
        </div>
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
    </div>
</div>
@endsection
