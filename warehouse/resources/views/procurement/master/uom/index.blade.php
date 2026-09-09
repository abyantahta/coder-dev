@extends('layouts.app')
@section('title','Master UOM')
@section('page-title','Master UOM (Satuan)')
@section('breadcrumb')
    <li class="breadcrumb-item">Pengadaan</li>
    <li class="breadcrumb-item active">UOM</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah UOM</div>
            <div class="card-body">
                <form action="{{ route('procurement.uom.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                               placeholder="PCS, BOX, RIM..." style="text-transform:uppercase;" maxlength="20">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               placeholder="Pieces, Box, Rim...">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-hover mb-0" id="uomTable">
                    <thead class="table-light">
                        <tr><th>Kode</th><th>Nama</th><th>Digunakan</th><th>Status</th><th width="120">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($uoms as $uom)
                        <tr>
                            <td><span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">{{ $uom->code }}</span></td>
                            <td>{{ $uom->name }}</td>
                            <td><span class="badge bg-secondary">{{ $uom->procurement_items_count }} item</span></td>
                            <td><span class="badge bg-{{ $uom->is_active ? 'success' : 'secondary' }}">{{ $uom->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="editUom({{ $uom->id }},'{{ $uom->code }}','{{ $uom->name }}',{{ $uom->is_active ? 1 : 0 }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('procurement.uom.destroy', $uom->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Hapus UOM {{ $uom->code }}?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada UOM</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:380px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Edit UOM</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode</label>
                        <input type="text" name="code" id="editCode" class="form-control" style="text-transform:uppercase;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama</label>
                        <input type="text" name="name" id="editName" class="form-control">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" id="editActive" value="1">
                        <label class="form-check-label fw-semibold" for="editActive">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if($uoms->count())
$('#uomTable').DataTable({ pageLength:15, language:{ search:'Cari:' } });
@endif

function editUom(id, code, name, active) {
    document.getElementById('editCode').value = code;
    document.getElementById('editName').value = name;
    document.getElementById('editActive').checked = active;
    document.getElementById('editForm').action = `/procurement/uom/${id}`;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endpush
