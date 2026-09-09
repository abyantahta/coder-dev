@extends('layouts.app')
@section('title','Master Kebutuhan ATK')
@section('page-title','Master Kebutuhan ATK')
@section('breadcrumb')
    <li class="breadcrumb-item">Pengadaan</li>
    <li class="breadcrumb-item active">Kebutuhan ATK</li>
@endsection

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle me-2 text-primary"></i>Tambah Kebutuhan ATK</div>
            <div class="card-body">
                <form action="{{ route('procurement.purposes.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kebutuhan <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               placeholder="contoh: Kebutuhan ATK Bulanan Produksi" value="{{ old('name') }}">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text" style="font-size:11px;">Akan muncul sebagai pilihan dropdown "Keperluan/Tujuan" di form buat permintaan.</div>
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
                <table class="table table-hover mb-0" id="purposeTable">
                    <thead class="table-light">
                        <tr><th>Nama Kebutuhan</th><th>Status</th><th width="120">Aksi</th></tr>
                    </thead>
                    <tbody>
                        @forelse($purposes as $purpose)
                        <tr>
                            <td>{{ $purpose->name }}</td>
                            <td><span class="badge bg-{{ $purpose->is_active ? 'success' : 'secondary' }}">{{ $purpose->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="editPurpose({{ $purpose->id }},{{ Js::from($purpose->name) }},{{ $purpose->is_active ? 1 : 0 }})">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="{{ route('procurement.purposes.destroy', $purpose->id) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Hapus kebutuhan ini?')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada data. Tambahkan kebutuhan ATK pertama di form sebelah kiri.</td></tr>
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
            <div class="modal-header border-0"><h6 class="modal-title fw-bold">Edit Kebutuhan ATK</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form id="editForm" method="POST">
                @csrf @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Kebutuhan</label>
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
@if($purposes->count())
$('#purposeTable').DataTable({ pageLength:15, language:{ search:'Cari:' } });
@endif

function editPurpose(id, name, active) {
    document.getElementById('editName').value = name;
    document.getElementById('editActive').checked = active;
    document.getElementById('editForm').action = `/procurement/purposes/${id}`;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
@endpush
