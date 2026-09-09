@extends('layouts.app')
@section('title','Master Prefix Kode Item')
@section('page-title','Master Prefix Kode Item')
@section('breadcrumb')
    <li class="breadcrumb-item">Pengadaan</li>
    <li class="breadcrumb-item active">Prefix Kode Item</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Prefix</div>
            <div class="card-body">
                <form action="{{ route('procurement.item-prefixes.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kode Prefix <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control @error('code') is-invalid @enderror"
                               placeholder="contoh: OEQ" maxlength="10" style="text-transform:uppercase;" value="{{ old('code') }}">
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text" style="font-size:11px;">Huruf saja, tanpa spasi/angka (mis. ATK, OEQ, ITM).</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama / Keterangan <span class="text-danger">*</span></label>
                        <input type="text" name="label" class="form-control @error('label') is-invalid @enderror"
                               placeholder="contoh: Office Equipment" value="{{ old('label') }}">
                        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                <table class="table table-hover mb-0" id="prefixTable">
                    <thead class="table-light">
                        <tr><th>Kode</th><th>Nama / Keterangan</th><th>Jumlah Item</th><th>Status</th><th width="120">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($prefixes as $prefix)
                        <tr>
                            <td><span class="fw-semibold text-primary">{{ $prefix->code }}</span></td>
                            <td>{{ $prefix->label }}</td>
                            <td>{{ \App\Models\ProcurementItem::where('item_code', 'like', $prefix->code.'-%')->count() }}</td>
                            <td><span class="badge bg-{{ $prefix->is_active ? 'success' : 'secondary' }}">{{ $prefix->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="editPrefix({{ $prefix->id }},{{ Js::from($prefix->label) }},{{ $prefix->is_active ? 1 : 0 }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('procurement.item-prefixes.destroy', $prefix->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Hapus prefix ini?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data. Tambahkan prefix pertama di form sebelah kiri.</td></tr>
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
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:16px;">
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Edit Prefix</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama / Keterangan</label>
                        <input type="text" name="label" id="editLabel" class="form-control">
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
@if($prefixes->count())
$('#prefixTable').DataTable({ paging:false, info:false, language:{ search:'Cari:' } });
@endif

function editPrefix(id, label, active) {
    document.getElementById('editLabel').value = label;
    document.getElementById('editActive').checked = active;
    document.getElementById('editForm').action = `/procurement/item-prefixes/${id}`;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endpush
