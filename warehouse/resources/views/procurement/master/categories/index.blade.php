@extends('layouts.app')
@section('title','Master Kategori Item')
@section('page-title','Master Kategori Item')
@section('breadcrumb')
    <li class="breadcrumb-item">Pengadaan</li>
    <li class="breadcrumb-item active">Kategori Item</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Kategori</div>
            <div class="card-body">
                <form action="{{ route('procurement.categories.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               placeholder="contoh: Alat Tulis" value="{{ old('name') }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent Kategori</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- Tidak ada (kategori utama) --</option>
                            @foreach($parents as $p)
                            <option value="{{ $p->id }}" {{ old('parent_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text" style="font-size:11px;">Kosongkan kalau ini kategori utama. Pilih parent kalau ini sub-kategori (misal "CD - DVD" di bawah "IT Accessories").</div>
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
                <table class="table table-hover mb-0" id="categoryTable">
                    <thead class="table-light">
                        <tr><th>Nama Kategori</th><th>Parent</th><th>Jumlah Item</th><th>Status</th><th width="120">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $category)
                        <tr>
                            <td>
                                @if($category->parent_id)
                                    <span class="text-muted me-1">↳</span>
                                @endif
                                {{ $category->name }}
                            </td>
                            <td>{{ $category->parent?->name ?? '-' }}</td>
                            <td>{{ $category->items()->count() }}</td>
                            <td><span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="editCategory({{ $category->id }},{{ Js::from($category->name) }},{{ $category->is_active ? 1 : 0 }},{{ $category->parent_id ?? 'null' }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('procurement.categories.destroy', $category->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Hapus kategori ini?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada data. Tambahkan kategori pertama di form sebelah kiri.</td></tr>
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
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Edit Kategori</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kategori</label>
                        <input type="text" name="name" id="editName" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Parent Kategori</label>
                        <select name="parent_id" id="editParent" class="form-select">
                            <option value="">-- Tidak ada (kategori utama) --</option>
                            @foreach($parents as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
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
@if($categories->count())
$('#categoryTable').DataTable({ pageLength:15, language:{ search:'Cari:' } });
@endif

function editCategory(id, name, active, parentId) {
    document.getElementById('editName').value = name;
    document.getElementById('editActive').checked = active;
    document.getElementById('editParent').value = parentId ?? '';
    document.getElementById('editForm').action = `/procurement/categories/${id}`;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endpush
