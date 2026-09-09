@extends('layouts.app')
@section('title', 'Departemen')
@section('page-title', 'Master Departemen')
@section('breadcrumb')
    <li class="breadcrumb-item">Master Data</li>
    <li class="breadcrumb-item active">Departemen</li>
@endsection
@section('page-actions')
    @if(auth()->user()->isAdmin())
    <a href="{{ route('master.departments.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Departemen
    </a>
    @endif
@endsection

@section('content')
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="deptTable">
                <thead class="table-light">
                    <tr>
                        <th width="5%">#</th>
                        <th width="15%">Kode</th>
                        <th>Nama Departemen</th>
                        <th width="12%">Jumlah User</th>
                        <th width="10%">Status</th>
                        <th width="12%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $i => $dept)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary fw-semibold">{{ $dept->code }}</span></td>
                        <td>{{ $dept->name }}</td>
                        <td>
                            <span class="badge bg-secondary">{{ $dept->users_count }} user</span>
                        </td>
                        <td>
                            @if($dept->is_active)
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            @if(auth()->user()->isAdmin())
                            <a href="{{ route('master.departments.edit', $dept->id) }}"
                               class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button onclick="deleteDept({{ $dept->id }}, '{{ $dept->name }}')"
                                    class="btn btn-sm btn-outline-danger" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada departemen</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<form id="deleteForm" method="POST" style="display:none;">
    @csrf @method('DELETE')
</form>
@endsection

@push('scripts')
<script>
@if($departments->count())
$('#deptTable').DataTable({
    pageLength: 15,
    language: {
        search: 'Cari:', paginate: { previous: '&laquo;', next: '&raquo;' },
        lengthMenu: 'Tampilkan _MENU_ data', info: 'Menampilkan _START_-_END_ dari _TOTAL_ data',
        zeroRecords: 'Tidak ada data ditemukan', emptyTable: 'Belum ada data'
    }
});
@endif

function deleteDept(id, name) {
    Swal.fire({
        title: 'Hapus Departemen?',
        text: `Departemen "${name}" akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            const form = document.getElementById('deleteForm');
            form.action = `/master/departments/${id}`;
            form.submit();
        }
    });
}
</script>
@endpush
