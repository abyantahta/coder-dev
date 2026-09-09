@extends('layouts.app')
@section('title', 'Stok Minimum')
@section('page-title', 'Manajemen Stok Minimum')
@section('breadcrumb')
    <li class="breadcrumb-item">Admin</li>
    <li class="breadcrumb-item active">Stok Minimum</li>
@endsection
@section('page-actions')
    @if($belowMinCount > 0)
    <form action="{{ route('admin.min-stock.generate-pr') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-warning btn-sm"
            onclick="return confirm('Generate Purchase Request untuk {{ $belowMinCount }} item di bawah minimum?')">
            <i class="bi bi-file-earmark-plus me-1"></i>Generate PR ({{ $belowMinCount }} item)
        </button>
    </form>
    @endif
@endsection

@section('content')

@if($belowMinCount > 0)
<div class="alert alert-warning d-flex gap-2 align-items-center mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
        <strong>{{ $belowMinCount }} item</strong> berada di bawah stok minimum.
        Klik <strong>"Generate PR"</strong> untuk membuat Purchase Request ke Purchasing.
    </div>
</div>
@endif

<div class="card">
    <div class="card-header"><i class="bi bi-bar-chart me-2 text-primary"></i>Daftar Stok & Minimum</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="stockTable">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th>Kategori</th>
                        <th>Tipe</th>
                        <th width="120">Stok Saat Ini</th>
                        <th width="130">Stok Minimum</th>
                        <th width="80">Aktif</th>
                        <th width="80">Status</th>
                        <th width="80">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr class="{{ $item->below_minimum ? 'table-warning' : '' }}" id="row-{{ $item->id }}">
                        <td>
                            <div class="fw-semibold">{{ $item->description }}</div>
                            <div class="text-muted" style="font-size:11px;">{{ $item->item_code }}</div>
                        </td>
                        <td><span class="badge bg-secondary bg-opacity-15 text-secondary">{{ $item->category ?? '-' }}</span></td>
                        <td>
                            @if($item->is_memo)
                                <span class="badge bg-info bg-opacity-10 text-info">Memo</span>
                            @else
                                <span class="badge bg-primary bg-opacity-10 text-primary">QAD</span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold {{ $item->below_minimum ? 'text-danger' : 'text-success' }}">
                                {{ number_format($item->current_qty, 2) }}
                            </span>
                            <span class="text-muted" style="font-size:11px;"> {{ $item->unit }}</span>
                        </td>
                        <td>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control min-qty-input"
                                       id="minqty-{{ $item->id }}"
                                       value="{{ $item->minimumStock?->min_qty ?? 0 }}"
                                       min="0" step="0.01" style="width:70px;">
                                <span class="input-group-text" style="font-size:11px;">{{ $item->unit }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-flex justify-content-center">
                                <input class="form-check-input active-toggle" type="checkbox"
                                       id="active-{{ $item->id }}"
                                       {{ $item->minimumStock?->is_active ? 'checked' : '' }}>
                            </div>
                        </td>
                        <td>
                            @if($item->below_minimum)
                                <span class="badge bg-danger">Rendah</span>
                            @elseif($item->minimumStock)
                                <span class="badge bg-success">Aman</span>
                            @else
                                <span class="badge bg-secondary">-</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="saveMinStock({{ $item->id }})">
                                <i class="bi bi-save"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$('#stockTable').DataTable({
    pageLength: 20,
    language: { search: 'Cari:', paginate: { previous: '&laquo;', next: '&raquo;' }, info: 'Menampilkan _START_-_END_ dari _TOTAL_', zeroRecords: 'Tidak ada data' },
    columnDefs: [{ orderable: false, targets: [4,5,7] }]
});

async function saveMinStock(itemId) {
    const minQty   = document.getElementById(`minqty-${itemId}`).value;
    const isActive = document.getElementById(`active-${itemId}`).checked;

    const res = await fetch(`/admin/min-stock/${itemId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify({ min_qty: minQty, is_active: isActive ? 1 : 0 })
    });
    const data = await res.json();

    if (data.success) {
        Swal.fire({ icon: 'success', title: 'Tersimpan', text: data.message,
            timer: 1500, showConfirmButton: false, toast: true, position: 'top-end' });
    } else {
        Swal.fire({ icon: 'error', title: 'Gagal', text: data.message });
    }
}
</script>
@endpush
