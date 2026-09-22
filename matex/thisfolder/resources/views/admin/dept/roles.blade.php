@extends('layouts.app')
@section('title', 'Kelola Roles — ' . $dept->name)
@section('page-title', 'Kelola Roles — ' . $dept->name)

@section('content')

@if (session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
@endif
@if (session('error'))
<div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ session('error') }}</div>
@endif

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('dept-admin.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke Dept Admin</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Role List --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Daftar Role</h3>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($roles as $role)
            <div class="px-5 py-4">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-slate-800">{{ $role->name }}</span>
                            @if ($role->is_superuser)
                                <span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-medium">Superuser</span>
                            @endif
                            @if (!$role->is_active)
                                <span class="text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">Nonaktif</span>
                            @endif
                        </div>
                        <div class="text-xs text-slate-400 font-mono mt-0.5">{{ $role->key }} · level {{ $role->level }} · sort {{ $role->sort_order }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">{{ $role->users_count }} user</div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button onclick="openEditRole({{ $role->id }}, {{ $role->toJson() }})"
                            class="text-xs text-violet-600 hover:text-violet-800 font-medium">Edit</button>
                        @if ($role->users_count == 0)
                        <form method="POST" action="{{ route('dept-admin.roles.destroy', $role) }}"
                            onsubmit="return confirm('Hapus role {{ $role->name }}?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-slate-400 text-sm">Belum ada role.</div>
            @endforelse
        </div>
    </div>

    {{-- Add / Edit Role Form --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4" id="role-form-title">Tambah Role Baru</h3>
        <form method="POST" id="role-form" action="{{ route('dept-admin.roles.store') }}" class="space-y-4">
            @csrf
            <span id="role-method-field"></span>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Role <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="role-name" required
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500"
                    placeholder="Contoh: Unit Head">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Key (slug) <span class="text-red-500">*</span></label>
                <input type="text" name="key" id="role-key" required
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-violet-500"
                    placeholder="unit_head">
                <p class="text-xs text-slate-400 mt-1">Huruf, angka, underscore/dash. Tidak bisa diubah jika sudah dipakai.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Level <span class="text-red-500">*</span></label>
                    <input type="number" name="level" id="role-level" required min="1" max="99" value="10"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <p class="text-xs text-slate-400 mt-1">1 = tertinggi</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sort Order <span class="text-red-500">*</span></label>
                    <input type="number" name="sort_order" id="role-sort" required min="0" value="{{ $roles->count() + 1 }}"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>
            </div>

            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_superuser" id="role-superuser" value="1" class="rounded">
                    Superuser (akses Dept Admin)
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700" id="role-active-wrap" style="display:none">
                    <input type="checkbox" name="is_active" id="role-active" value="1" checked class="rounded">
                    Aktif
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="bg-violet-600 hover:bg-violet-700 text-white rounded-lg font-semibold px-5 py-2 text-sm transition">
                    Simpan Role
                </button>
                <button type="button" onclick="resetRoleForm()"
                    class="text-sm text-slate-500 hover:text-slate-700">Reset</button>
            </div>
        </form>
    </div>

</div>

@endsection

@push('scripts')
<script>
function openEditRole(id, role) {
    document.getElementById('role-form-title').textContent = 'Edit Role';
    document.getElementById('role-form').action = '/dept-admin/roles/' + id;
    document.getElementById('role-method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('role-name').value     = role.name;
    document.getElementById('role-key').value      = role.key;
    document.getElementById('role-level').value    = role.level;
    document.getElementById('role-sort').value     = role.sort_order;
    document.getElementById('role-superuser').checked = !!role.is_superuser;
    document.getElementById('role-active').checked    = !!role.is_active;
    document.getElementById('role-active-wrap').style.display = '';
    window.scrollTo(0, document.getElementById('role-form').getBoundingClientRect().top + window.scrollY - 80);
}

function resetRoleForm() {
    document.getElementById('role-form-title').textContent = 'Tambah Role Baru';
    document.getElementById('role-form').action = '{{ route('dept-admin.roles.store') }}';
    document.getElementById('role-method-field').innerHTML = '';
    document.getElementById('role-form').reset();
    document.getElementById('role-active-wrap').style.display = 'none';
}
</script>
@endpush
