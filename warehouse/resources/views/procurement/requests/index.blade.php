@extends('layouts.app')
@section('title','Permintaan Saya')
@section('page-title','Permintaan Pengadaan Saya')
@section('breadcrumb')
    <li class="breadcrumb-item">Pengadaan</li>
    <li class="breadcrumb-item active">Permintaan Saya</li>
@endsection
@section('page-actions')
    <a href="{{ route('procurement.requests.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Buat Permintaan
    </a>
@endsection

@section('content')
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Semua Status --</option>
                    @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Tanggal Dari</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Tanggal Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route('procurement.requests.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="reqTable">
                <thead class="table-light">
                    <tr>
                        <th>No. Permintaan</th><th>Keperluan</th><th>Departemen</th>
                        <th>Item</th><th>No. PO</th><th>Tanggal</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $req->req_no }}</span></td>
                        <td>{{ Str::limit($req->purpose, 40) }}</td>
                        <td>{{ $req->department?->name ?? '-' }}</td>
                        <td><span class="badge bg-secondary">{{ $req->details->count() }} item</span></td>
                        <td>{{ $req->aggregatedPr?->qad_po_no ?? '-' }}</td>
                        <td>{{ $req->req_date->format('d/m/Y') }}</td>
                        <td><span class="badge bg-{{ $req->getStatusBadge() }}">{{ $req->getStatusLabel() }}</span></td>
                        <td>
                            <a href="{{ route('procurement.requests.show', $req->id) }}" class="btn btn-sm btn-light">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size:32px;"></i>
                            <p class="mt-2 mb-3">Belum ada permintaan pengadaan</p>
                            <a href="{{ route('procurement.requests.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i>Buat Permintaan
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $requests->links('pagination::bootstrap-5') }}</div>
@endsection

@push('scripts')
<script>
@if($requests->count())
$('#reqTable').DataTable({ paging:false, info:false, order:[[5,'desc']], language:{ search:'Cari:' }, columnDefs:[{orderable:false,targets:[7]}] });
@endif
</script>
@endpush
