@extends('layouts.app')
@section('title', 'Purchase Request')
@section('page-title', 'Purchase Request')
@section('breadcrumb')
    <li class="breadcrumb-item">Admin</li>
    <li class="breadcrumb-item active">Purchase Request</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="prTable">
                <thead class="table-light">
                    <tr>
                        <th>No. PR</th>
                        <th>Tanggal</th>
                        <th>Dibuat Oleh</th>
                        <th>Jumlah Item</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($prs as $pr)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $pr->pr_no }}</span></td>
                        <td>{{ $pr->created_at->format('d/m/Y') }}</td>
                        <td>{{ $pr->creator->name }}</td>
                        <td><span class="badge bg-secondary">{{ $pr->details->count() }} item</span></td>
                        <td>
                            <span class="badge bg-{{ $pr->getStatusBadge() }}">{{ $pr->getStatusLabel() }}</span>
                        </td>
                        <td>{{ $pr->submitted_at?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('admin.purchase-requests.show', $pr->id) }}"
                                   class="btn btn-sm btn-outline-primary" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.purchase-requests.pdf', $pr->id) }}"
                                   class="btn btn-sm btn-outline-danger" target="_blank" title="Print PDF">
                                    <i class="bi bi-file-pdf"></i>
                                </a>
                                @if($pr->status === 'draft')
                                <form action="{{ route('admin.purchase-requests.submit', $pr->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary"
                                            onclick="return confirm('Submit PR ini ke Purchasing?')" title="Submit">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        Belum ada Purchase Request. <a href="{{ route('admin.min-stock.index') }}">Cek stok minimum</a> untuk generate PR.
                    </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $prs->links('pagination::bootstrap-5') }}</div>
@endsection

@push('scripts')
<script>
@if($prs->count())
$('#prTable').DataTable({
    paging: false, info: false,
    language: { search: 'Cari:', zeroRecords: 'Tidak ada data' },
    columnDefs: [{ orderable: false, targets: [6] }]
});
@endif
</script>
@endpush
