@extends('layouts.app')
@section('title', 'Master Item')
@section('page-title', 'Master Item')
@section('breadcrumb')
    <li class="breadcrumb-item">Master Data</li>
    <li class="breadcrumb-item active">Item</li>
@endsection
@section('page-actions')
    @if(auth()->user()->isSuperAdmin())
    <form action="{{ route('master.items.sync') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-success btn-sm me-2"
                onclick="return confirm('Sync data item dari QAD? Proses ini mungkin memakan waktu.')">
            <i class="bi bi-arrow-repeat me-1"></i>Sync dari QAD
        </button>
    </form>
    @endif
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="itemTable">
                <thead class="table-light">
                    <tr>
                        <th>Item Code</th>
                        <th>Deskripsi</th>
                        <th>Kategori</th>
                        <th>Satuan</th>
                        <th>Tipe</th>
                        <th>Stok</th>
                        <th>Min. Stok</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $item->item_code }}</span></td>
                        <td>
                            <div class="d-flex gap-2 align-items-center">
                                @if($item->photo)
                                <img src="{{ asset('storage/'.$item->photo) }}" style="width:32px;height:32px;border-radius:6px;object-fit:cover;">
                                @endif
                                {{ $item->description }}
                            </div>
                        </td>
                        <td><span class="badge bg-secondary bg-opacity-15 text-secondary">{{ $item->category ?? '-' }}</span></td>
                        <td>{{ $item->unit ?? '-' }}</td>
                        <td>
                            @if($item->is_memo)
                                <span class="badge bg-info bg-opacity-10 text-info">Memo</span>
                            @else
                                <span class="badge bg-primary bg-opacity-10 text-primary">QAD</span>
                            @endif
                        </td>
                        <td>
                            @php $qty = $item->getQtyOnHand(); @endphp
                            <span class="{{ $qty <= 0 ? 'text-danger fw-bold' : 'text-success' }}">
                                {{ number_format($qty, 0) }}
                            </span>
                        </td>
                        <td>{{ $item->minimumStock ? number_format($item->minimumStock->min_qty, 0) : '-' }}</td>
                        <td>
                            <span class="badge bg-{{ $item->is_active ? 'success' : 'secondary' }}">
                                {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('master.items.edit', $item->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada item. Sync dari QAD untuk memuat data.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $items->links('pagination::bootstrap-5') }}</div>
@endsection

@push('scripts')
<script>
@if($items->count())
$('#itemTable').DataTable({
    paging: false, info: false,
    language: { search: 'Cari:', zeroRecords: 'Tidak ada data' },
    columnDefs: [{ orderable: false, targets: [8] }]
});
@endif
</script>
@endpush
