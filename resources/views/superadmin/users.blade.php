@extends('layouts.app')
@section('title', 'Super Admin — Semua Users')
@section('page-title', 'Super Admin — Manajemen Users')

@section('content')

{{-- Filter --}}
<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Cari</label>
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Nama / email…"
            class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 w-48">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Department</label>
        <select name="dept_id" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
            <option value="">Semua Dept</option>
            @foreach ($departments as $dept)
            <option value="{{ $dept->id }}" {{ request('dept_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit"
        class="bg-slate-700 hover:bg-slate-800 text-white text-sm px-4 py-1.5 rounded-lg transition">Filter</button>
    @if (request()->hasAny(['search','dept_id']))
    <a href="{{ route('superadmin.users.index') }}" class="text-sm text-slate-500 hover:text-slate-700 py-1.5">Reset</a>
    @endif
    <div class="ml-auto">
        <button type="button" onclick="document.getElementById('add-user-form').classList.toggle('hidden')"
            class="inline-flex items-center gap-1.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah User
        </button>
    </div>
</form>

{{-- Add User Form --}}
@php $addUserOpen = $errors->any() && old('_form') === 'add_user'; @endphp
<div id="add-user-form" class="{{ $addUserOpen ? '' : 'hidden' }} bg-white rounded-xl shadow-sm p-6 mb-4 border-2 border-violet-200">
    <h3 class="font-semibold text-slate-800 mb-4">Tambah User Baru</h3>

    @if ($addUserOpen && $errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-4 text-sm text-red-700">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('superadmin.users.store') }}">
        @csrf
        <input type="hidden" name="_form" value="add_user">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Nama <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Password <span class="text-red-500">*</span></label>
                <input type="password" name="password" required minlength="6"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Department</label>
                <select name="department_id" id="new-dept-select"
                    onchange="updateRoles('new-role-select', this.value)"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="">— Tidak ada (requester biasa) —</option>
                    @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Role Dept</label>
                <select name="dept_role_id" id="new-role-select"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="">— Pilih Role —</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Role (sistem lama)</label>
                <select name="role"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>User (requester)</option>
                    <option value="member" {{ old('role') === 'member' ? 'selected' : '' }}>Member</option>
                    <option value="group_head" {{ old('role') === 'group_head' ? 'selected' : '' }}>Group Head</option>
                    <option value="unit_head" {{ old('role') === 'unit_head' ? 'selected' : '' }}>Unit Head</option>
                    <option value="section_head" {{ old('role') === 'section_head' ? 'selected' : '' }}>Section Head</option>
                    <option value="warehouse_mtc" {{ old('role') === 'warehouse_mtc' ? 'selected' : '' }}>Warehouse MTC</option>
                    <option value="qa_member" {{ old('role') === 'qa_member' ? 'selected' : '' }}>QA Member</option>
                    <option value="qa_group_head" {{ old('role') === 'qa_group_head' ? 'selected' : '' }}>QA Group Head</option>
                    <option value="qa_section_head" {{ old('role') === 'qa_section_head' ? 'selected' : '' }}>QA Section Head</option>
                </select>
            </div>
            <div class="col-span-2">
                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_superadmin" value="1" {{ old('is_superadmin') ? 'checked' : '' }}
                        class="rounded border-slate-300 text-violet-600">
                    <span>IT Superadmin <span class="text-xs text-slate-400">(akses penuh ke semua dept)</span></span>
                </label>
            </div>
        </div>
        <div class="flex gap-3 mt-5">
            <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">
                Buat User
            </button>
            <button type="button" onclick="document.getElementById('add-user-form').classList.add('hidden')"
                class="text-sm text-slate-500 hover:text-slate-700 px-4 py-2">Batal</button>
        </div>
    </form>
</div>

{{-- User Table --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Nama</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Email</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Department</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Role Dept</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Flag</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $u)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800">{{ $u->name }}</div>
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-xs font-mono">{{ $u->email }}</td>
                    <td class="px-4 py-3 text-slate-600 text-xs">{{ $u->dept?->name ?? ($u->department ?? '—') }}</td>
                    <td class="px-4 py-3 text-slate-600 text-xs">{{ $u->deptRole?->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if ($u->is_superadmin)
                        <span class="text-xs px-2 py-0.5 rounded-full bg-violet-100 text-violet-700 font-semibold">SuperAdmin</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <button onclick="document.getElementById('edit-user-{{ $u->id }}').classList.toggle('hidden')"
                            class="text-xs text-violet-600 hover:text-violet-800 font-medium mr-2">Edit</button>
                        <form method="POST" action="{{ route('superadmin.users.destroy', $u) }}"
                            onsubmit="return confirm('Hapus user {{ $u->name }}?')"
                            class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-400 hover:text-red-600 font-medium">Hapus</button>
                        </form>
                    </td>
                </tr>
                {{-- Inline Edit Row --}}
                <tr id="edit-user-{{ $u->id }}" class="hidden bg-violet-50 border-t border-violet-100">
                    <td colspan="6" class="px-4 py-5">
                        <h4 class="text-sm font-semibold text-slate-700 mb-3">Edit: {{ $u->name }}</h4>
                        <form method="POST" action="{{ route('superadmin.users.update', $u) }}">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Nama *</label>
                                    <input type="text" name="name" value="{{ $u->name }}" required
                                        class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Email *</label>
                                    <input type="email" name="email" value="{{ $u->email }}" required
                                        class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Password baru <span class="text-slate-400">(kosongkan = tidak ganti)</span></label>
                                    <input type="password" name="password" minlength="6"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Department</label>
                                    <select name="department_id"
                                        onchange="updateRoles('edit-role-{{ $u->id }}', this.value)"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                        <option value="">— Tidak ada —</option>
                                        @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ $u->department_id == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Role Dept</label>
                                    <select name="dept_role_id" id="edit-role-{{ $u->id }}"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                        <option value="">— Pilih Role —</option>
                                        @if ($u->department_id && isset($rolesByDept[$u->department_id]))
                                        @foreach ($rolesByDept[$u->department_id] as $r)
                                        <option value="{{ $r->id }}" {{ $u->dept_role_id == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                                        @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Role (sistem)</label>
                                    <select name="role"
                                        class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                                        @foreach (['user','member','group_head','unit_head','section_head','warehouse_mtc','qa_member','qa_group_head','qa_section_head'] as $r)
                                        <option value="{{ $r }}" {{ $u->role === $r ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$r)) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-3">
                                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                        <input type="checkbox" name="is_superadmin" value="1" {{ $u->is_superadmin ? 'checked' : '' }}
                                            class="rounded border-slate-300 text-violet-600">
                                        IT Superadmin
                                    </label>
                                </div>
                            </div>
                            <div class="flex gap-3 mt-4">
                                <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-semibold px-4 py-1.5 rounded-lg text-sm transition">
                                    Simpan
                                </button>
                                <button type="button"
                                    onclick="document.getElementById('edit-user-{{ $u->id }}').classList.add('hidden')"
                                    class="text-sm text-slate-500 hover:text-slate-700">Batal</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-slate-400 py-10">Tidak ada user ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($users->hasPages())
    <div class="px-4 py-3 border-t border-slate-100">
        {{ $users->links() }}
    </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
const rolesByDept = @json($rolesByDept);

function updateRoles(selectId, deptId) {
    const sel = document.getElementById(selectId);
    if (!sel) return;
    const roles = deptId && rolesByDept[deptId] ? rolesByDept[deptId] : [];
    sel.innerHTML = '<option value="">— Pilih Role —</option>';
    roles.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.id;
        opt.textContent = r.name;
        sel.appendChild(opt);
    });
}
</script>
@endpush
