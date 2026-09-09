@extends('layouts.app')
@section('title','Detail Permintaan')
@section('page-title', $procurementRequest->req_no)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('procurement.requests.index') }}" class="text-decoration-none">Permintaan</a></li>
    <li class="breadcrumb-item active">{{ $procurementRequest->req_no }}</li>
@endsection

@push('styles')
<style>
.timeline { position:relative; padding-left:0; list-style:none; display:flex; align-items:flex-start; }
.timeline-item {
    display:flex; flex-direction:column; align-items:center; text-align:center;
    flex:1; gap:0; position:relative; padding:0 4px;
}
.timeline-item:not(:last-child)::before {
    content:''; position:absolute; left:calc(50% + 24px); right:calc(-50% + 24px); top:19px;
    height:2px; background:#e9ecef;
}
.timeline-item.done:not(:last-child)::before { background:#198754; }
.timeline-icon {
    width:40px; height:40px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center; font-size:16px;
    border:2px solid #e9ecef; background:white; z-index:1;
}
.timeline-icon.done    { background:#198754; border-color:#198754; color:white; }
.timeline-icon.active  { background:#ffc107; border-color:#ffc107; color:white; animation: pulse 1.5s infinite; }
.timeline-icon.rejected{ background:#dc3545; border-color:#dc3545; color:white; }
.timeline-icon.pending { background:#f8f9fa; border-color:#dee2e6; color:#adb5bd; }
@keyframes pulse { 0%,100%{box-shadow:0 0 0 0 rgba(255,193,7,.4);} 50%{box-shadow:0 0 0 8px rgba(255,193,7,0);} }
.timeline-content { padding-top:8px; width:100%; }
.timeline-label { font-size:12px; font-weight:600; margin-bottom:2px; }
.timeline-meta { font-size:11px; color:#6c757d; }
.timeline-notes { font-size:11px; color:#495057; background:#f8f9fa; border-radius:6px; padding:6px 8px; margin-top:6px; text-align:left; }
</style>
@endpush

@section('content')

{{-- Timeline (mendatar, full width) --}}
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-diagram-3 me-2 text-primary"></i>Timeline Progress</div>
    <div class="card-body">
        @php $timeline = $procurementRequest->getTimeline(); @endphp
        <ul class="timeline">
            @foreach($timeline as $step)
            @php
                $iconClass = 'pending';
                if (!empty($step['rejected'])) $iconClass = 'rejected';
                elseif ($step['done']) $iconClass = 'done';
                elseif (!empty($step['active'])) $iconClass = 'active';
            @endphp
            <li class="timeline-item {{ $step['done'] ? 'done' : '' }}">
                <div class="timeline-icon {{ $iconClass }}">
                    @if(!empty($step['rejected']))
                        <i class="bi bi-x-lg"></i>
                    @elseif($step['done'])
                        <i class="bi bi-check-lg"></i>
                    @elseif($iconClass === 'active')
                        <i class="bi bi-clock"></i>
                    @else
                        <i class="bi {{ $step['icon'] }}"></i>
                    @endif
                </div>
                <div class="timeline-content">
                    <div class="timeline-label {{ !empty($step['rejected']) ? 'text-danger' : ($step['done'] ? 'text-success' : ($iconClass === 'active' ? 'text-warning' : 'text-muted')) }}">
                        {{ $step['label'] }}
                    </div>
                    @if($step['done'] || !empty($step['rejected']))
                        <div class="timeline-meta">
                            @if($step['by'])<span class="fw-semibold">{{ $step['by'] }}</span>@endif
                            @if($step['at']) — {{ $step['at']->format('d/m/Y H:i') }}@endif
                        </div>
                        @if(!empty($step['notes']))
                        <div class="timeline-notes">{{ $step['notes'] }}</div>
                        @endif
                    @elseif($iconClass === 'active')
                        <div class="timeline-meta text-warning">
                            <i class="bi bi-clock me-1"></i>Menunggu persetujuan...
                        </div>
                    @else
                        <div class="timeline-meta">Belum diproses</div>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
    </div>
</div>

<div class="row g-3">
    {{-- LEFT: Info --}}
    <div class="col-lg-5">

        {{-- Info Card --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-info-circle me-2 text-primary"></i>Info Permintaan</span>
                <span class="badge bg-{{ $procurementRequest->getStatusBadge() }} px-3 py-2" style="font-size:12px;">
                    {{ $procurementRequest->getStatusLabel() }}
                </span>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-3">
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;width:110px;">No. Permintaan</td>
                        <td class="fw-bold text-primary">{{ $procurementRequest->req_no }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Pemohon</td>
                        <td>{{ $procurementRequest->user->name }} <span class="text-muted">({{ $procurementRequest->user->npk }})</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Departemen</td>
                        <td>{{ $procurementRequest->department?->name ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Keperluan</td>
                        <td>{{ $procurementRequest->purpose }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Tanggal</td>
                        <td>{{ $procurementRequest->req_date->format('d/m/Y') }}</td>
                    </tr>
                    @if($procurementRequest->aggregatedPr)
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">No. PR (GA)</td>
                        <td class="fw-bold text-dark">{{ $procurementRequest->aggregatedPr->pr_no }}</td>
                    </tr>
                    @if($procurementRequest->aggregatedPr->qad_req_no)
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">No. PR (QAD)</td>
                        <td class="fw-bold text-primary">{{ $procurementRequest->aggregatedPr->qad_req_no }}</td>
                    </tr>
                    @endif
                    @if($procurementRequest->aggregatedPr->qad_po_no)
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">No. PO (QAD)</td>
                        <td class="fw-bold text-success">{{ $procurementRequest->aggregatedPr->qad_po_no }}</td>
                    </tr>
                    @endif
                    @endif
                    @if($procurementRequest->notes)
                    <tr>
                        <td class="text-muted fw-semibold" style="font-size:12px;">Catatan</td>
                        <td>{{ $procurementRequest->notes }}</td>
                    </tr>
                    @endif
                </table>

                @if($procurementRequest->status === 'rejected')
                <div class="alert alert-danger py-2 mb-3">
                    <div class="fw-semibold" style="font-size:13px;">
                        <i class="bi bi-x-circle me-1"></i>Ditolak pada tahap: {{ ucfirst($procurementRequest->rejected_stage) }}
                    </div>
                    <div style="font-size:12px;">{{ $procurementRequest->reject_reason }}</div>
                </div>
                @endif

                @if($procurementRequest->status === 'aggregated' && $procurementRequest->aggregatedPr)
                <div class="alert alert-primary py-2 mb-3 d-flex gap-2 align-items-center">
                    <i class="bi bi-diagram-3 fs-5"></i>
                    <div>
                        <div class="fw-semibold" style="font-size:13px;">Sudah diproses General Affair</div>
                        <div style="font-size:12px;">Digabung ke PR {{ $procurementRequest->aggregatedPr->pr_no }}
                            (<span class="fw-semibold">{{ $procurementRequest->aggregatedPr->getStatusLabel() }}</span>).</div>
                    </div>
                </div>
                @endif

                @if($procurementRequest->status === 'draft' && $procurementRequest->user_id === auth()->id())
                <form action="{{ route('procurement.requests.submit', $procurementRequest->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100"
                            onclick="return confirm('Ajukan permintaan ini untuk disetujui?')">
                        <i class="bi bi-send me-1"></i>Ajukan ke Section
                    </button>
                </form>
                @endif
            </div>
        </div>

        {{-- Lampiran --}}
        @if($procurementRequest->attachments->count())
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-paperclip me-2 text-primary"></i>Lampiran ({{ $procurementRequest->attachments->count() }})</div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @foreach($procurementRequest->attachments as $att)
                    <li class="list-group-item d-flex align-items-center gap-2" style="font-size:13px;">
                        <i class="bi {{ $att->getIconClass() }} fs-5"></i>
                        <a href="javascript:void(0)" onclick="previewAttachment({{ Js::from(asset('storage/'.$att->file_path)) }}, {{ Js::from($att->original_name) }}, {{ Js::from($att->mime_type) }})" class="text-truncate flex-grow-1 text-decoration-none">
                            {{ $att->original_name }}
                        </a>
                        <span class="text-muted" style="font-size:11px;">{{ $att->getFormattedSize() }}</span>
                        @if($att->uploaded_by === auth()->id() || auth()->user()->isAdmin())
                        <form action="{{ route('procurement.requests.attachments.destroy', $att->id) }}" method="POST" onsubmit="return confirm('Hapus lampiran ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                        </form>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif
    </div>

    {{-- RIGHT: Detail Items --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-ul me-2 text-primary"></i>
                Daftar Item ({{ $procurementRequest->details->count() }})
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Item</th>
                            <th width="10%">Kategori</th>
                            <th width="10%">Qty</th>
                            <th width="8%">UOM</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($procurementRequest->details as $i => $detail)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>
                                <div class="fw-semibold">{{ $detail->item->name }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $detail->item->item_code }}</div>
                            </td>
                            <td>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:10px;">
                                    {{ $detail->item->category?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="fw-bold">{{ number_format($detail->qty, 2) }}</td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info">{{ $detail->uom->code }}</span>
                            </td>
                            <td class="text-muted" style="font-size:12px;">{{ $detail->notes ?: '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </div>
            </div>
            <div class="card-footer bg-transparent">
                <a href="{{ route('procurement.requests.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>

        @if(!empty($budgetHistory))
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-graph-up me-2 text-primary"></i>Budget vs Actual — 12 Bulan Terakhir</div>
            <div class="card-body">
                <div style="position:relative;height:260px;">
                    <canvas id="budgetChart"></canvas>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@if(!empty($budgetHistory))
@push('scripts')
<script>
const budgetChartEl = document.getElementById('budgetChart');
if (budgetChartEl) {
    const rupiah = (v) => 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    new Chart(budgetChartEl, {
        type: 'bar',
        data: {
            labels: @json(collect($budgetHistory)->pluck('label')),
            datasets: [
                {
                    type: 'line',
                    label: 'Budget',
                    data: @json(collect($budgetHistory)->pluck('budget')),
                    borderColor: '#2a78d6',
                    backgroundColor: '#2a78d6',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#2a78d6',
                    tension: 0,
                    fill: false,
                    order: 0,
                },
                {
                    type: 'bar',
                    label: 'Actual',
                    data: @json(collect($budgetHistory)->pluck('consumed')),
                    backgroundColor: '#eb6834',
                    borderRadius: 4,
                    maxBarThickness: 28,
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, boxHeight: 12, font: { size: 12 } } },
                tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${rupiah(ctx.parsed.y)}` } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#898781', font: { size: 11 } } },
                y: { grid: { color: '#e1e0d9' }, ticks: { color: '#898781', font: { size: 11 }, callback: (v) => rupiah(v) } },
            },
        },
    });
}
</script>
@endpush
@endif
