@extends('layouts.app')
@section('title', 'Detail PR')
@section('page-title', 'Purchase Request: ' . $purchaseRequest->pr_no)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.purchase-requests.index') }}" class="text-decoration-none">Purchase Request</a></li>
    <li class="breadcrumb-item active">{{ $purchaseRequest->pr_no }}</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Info PR</div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">No. PR</td><td class="fw-bold text-primary">{{ $purchaseRequest->pr_no }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Tanggal</td><td>{{ $purchaseRequest->created_at->format('d/m/Y H:i') }}</td></tr>
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Dibuat</td><td>{{ $purchaseRequest->creator->name }}</td></tr>
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Status</td>
                        <td><span class="badge bg-{{ $purchaseRequest->getStatusBadge() }}">{{ $purchaseRequest->getStatusLabel() }}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Sumber</td>
                        <td>{{ $purchaseRequest->source === 'ga_aggregation' ? 'Agregasi Kebutuhan GA' : 'Auto (Stok Minimum)' }}</td>
                    </tr>
                    @if($purchaseRequest->qad_req_no)
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">No. Req QAD</td>
                        <td class="fw-bold">{{ $purchaseRequest->qad_req_no }}</td>
                    </tr>
                    @endif
                    @if($purchaseRequest->submitted_at)
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Submitted</td><td>{{ $purchaseRequest->submitted_at->format('d/m/Y H:i') }}</td></tr>
                    @endif
                    @if($purchaseRequest->notes)
                    <tr><td class="text-muted fw-semibold" style="font-size:12px;">Catatan</td><td>{{ $purchaseRequest->notes }}</td></tr>
                    @endif
                </table>

                <div class="d-grid gap-2 mt-3">
                    <a href="{{ route('admin.purchase-requests.pdf', $purchaseRequest->id) }}" target="_blank"
                       class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-file-pdf me-1"></i>Download PDF
                    </a>
                    @if($purchaseRequest->status === 'draft')
                    <form action="{{ route('admin.purchase-requests.submit', $purchaseRequest->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm w-100"
                                onclick="return confirm('Submit PR ini ke Purchasing?')">
                            <i class="bi bi-send me-1"></i>Submit ke Purchasing
                        </button>
                    </form>
                    @endif
                    @if($purchaseRequest->status === 'submitted')
                    <form action="{{ route('admin.purchase-requests.update-status', $purchaseRequest->id) }}" method="POST">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="completed">
                        <button type="submit" class="btn btn-success btn-sm w-100"
                                onclick="return confirm('Tandai PR ini sebagai selesai?')">
                            <i class="bi bi-check-circle me-1"></i>Tandai Selesai
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-list-ul me-2 text-primary"></i>Item yang Dibutuhkan</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            @if($purchaseRequest->source === 'min_stock')
                            <th>Stok Saat Ini</th>
                            <th>Minimum Stok</th>
                            @endif
                            <th>Qty Dibutuhkan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseRequest->details as $i => $detail)
                        @php $resolved = $detail->resolvedItem(); @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $resolved->name }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $resolved->code }} | {{ $resolved->uom }}</div>
                            </td>
                            @if($purchaseRequest->source === 'min_stock')
                            <td><span class="text-danger fw-semibold">{{ number_format($detail->current_stock, 2) }}</span></td>
                            <td>{{ number_format($detail->min_stock, 2) }}</td>
                            @endif
                            <td><span class="text-primary fw-bold">{{ number_format($detail->qty_needed, 2) }}</span></td>
                        </tr>
                        @if($purchaseRequest->source === 'ga_aggregation' && $detail->sources->count())
                        <tr class="table-light">
                            <td></td>
                            <td colspan="2" style="font-size:11px;">
                                <span class="text-muted">Breakdown departemen:</span>
                                @foreach($detail->sources as $src)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary me-1">
                                        {{ $src->department->name }}: {{ number_format($src->qty, 2) }}
                                    </span>
                                @endforeach
                            </td>
                        </tr>
                        @endif
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
