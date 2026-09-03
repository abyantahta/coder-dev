@extends('layouts.app')
@section('title', 'Super Admin — Departments')
@section('page-title', 'Super Admin — Departments')

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-2xl font-bold text-slate-800">{{ $stats['total_depts'] }}</div>
        <div class="text-xs text-slate-500 mt-0.5">Total Departments</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-2xl font-bold text-slate-800">{{ $stats['total_users'] }}</div>
        <div class="text-xs text-slate-500 mt-0.5">Total Users</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-2xl font-bold text-slate-800">{{ $stats['total_wos'] }}</div>
        <div class="text-xs text-slate-500 mt-0.5">Total Work Orders</div>
    </div>
</div>

{{-- Header + Add button --}}
<div class="flex items-center justify-between mb-4">
    <h2 class="text-sm font-semibold text-slate-700">Daftar Departments</h2>
    <button onclick="document.getElementById('add-dept-form').classList.toggle('hidden')"
        class="inline-flex items-center gap-1.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah Department
    </button>
</div>

{{-- Add Department Form --}}
@php $addDeptOpen = $errors->any() && old('_form') === 'add_dept'; @endphp
<div id="add-dept-form" class="{{ $addDeptOpen ? '' : 'hidden' }} bg-white rounded-xl shadow-sm p-6 mb-6 border-2 border-violet-200">
    <h3 class="font-semibold text-slate-800 mb-4">Tambah Department Baru</h3>

    @if ($addDeptOpen && $errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-4 text-sm text-red-700">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('superadmin.departments.store') }}">
        @csrf
        <input type="hidden" name="_form" value="add_dept">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Nama Department <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="100"
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 {{ $errors->has('name') ? 'border-red-400' : 'border-slate-300' }}">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Kode (maks 10 karakter) <span class="text-red-500">*</span></label>
                <input type="text" name="code" value="{{ old('code') }}" required maxlength="10"
                    placeholder="MTC, QA, GA…"
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 {{ $errors->has('code') ? 'border-red-400' : 'border-slate-300' }}">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">
                    Slug <span class="text-red-500">*</span>
                    <span class="text-slate-400 font-normal">(huruf kecil, angka, tanda hubung)</span>
                </label>
                <input type="text" name="slug" value="{{ old('slug') }}" required maxlength="50"
                    placeholder="maintenance, qa, general-affairs…"
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 {{ $errors->has('slug') ? 'border-red-400' : 'border-slate-300' }}">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Warna <span class="text-red-500">*</span></label>
                <select name="color" required
                    class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 {{ $errors->has('color') ? 'border-red-400' : 'border-slate-300' }}">
                    <option value="">— Pilih Warna —</option>
                    @foreach (['blue','green','purple','red','orange','teal','slate'] as $c)
                    <option value="{{ $c }}" {{ old('color') === $c ? 'selected' : '' }}>{{ ucfirst($c) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2">
                <label class="block text-xs font-medium text-slate-600 mb-1">Deskripsi</label>
                <input type="text" name="description" value="{{ old('description') }}" maxlength="255"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
            </div>
            <div class="flex gap-6">
                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="has_warehouse" value="1" {{ old('has_warehouse') ? 'checked' : '' }}
                        class="rounded border-slate-300 text-violet-600">
                    Punya Warehouse
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="has_unit_structure" value="1" {{ old('has_unit_structure') ? 'checked' : '' }}
                        class="rounded border-slate-300 text-violet-600">
                    Struktur Unit/Group
                </label>
            </div>
        </div>
        <div class="flex gap-3 mt-5">
            <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition">
                Simpan Department
            </button>
            <button type="button" onclick="document.getElementById('add-dept-form').classList.add('hidden')"
                class="text-sm text-slate-500 hover:text-slate-700 px-4 py-2">Batal</button>
        </div>
    </form>
</div>

{{-- Department Cards --}}
@php
$colorMap = [
    'blue'   => 'bg-blue-50 border-blue-200 text-blue-800',
    'green'  => 'bg-green-50 border-green-200 text-green-800',
    'purple' => 'bg-purple-50 border-purple-200 text-purple-800',
    'red'    => 'bg-red-50 border-red-200 text-red-800',
    'orange' => 'bg-orange-50 border-orange-200 text-orange-800',
    'teal'   => 'bg-teal-50 border-teal-200 text-teal-800',
    'slate'  => 'bg-slate-50 border-slate-200 text-slate-800',
];
$dotMap = [
    'blue'   => 'bg-blue-500',
    'green'  => 'bg-green-500',
    'purple' => 'bg-purple-500',
    'red'    => 'bg-red-500',
    'orange' => 'bg-orange-500',
    'teal'   => 'bg-teal-500',
    'slate'  => 'bg-slate-500',
];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    @foreach ($departments as $dept)
    @php $cardClass = $colorMap[$dept->color] ?? 'bg-slate-50 border-slate-200 text-slate-800'; @endphp
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        {{-- Card header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="w-3 h-3 rounded-full {{ $dotMap[$dept->color] ?? 'bg-slate-400' }}"></div>
                <div>
                    <div class="font-semibold text-slate-800 flex items-center gap-2">
                        {{ $dept->name }}
                        <span class="text-xs font-mono px-1.5 py-0.5 rounded {{ $cardClass }}">{{ $dept->code }}</span>
                        @if (!$dept->is_active)
                        <span class="text-xs px-1.5 py-0.5 rounded-full bg-red-100 text-red-600">Nonaktif</span>
                        @endif
                    </div>
                    @if ($dept->description)
                    <div class="text-xs text-slate-500 mt-0.5">{{ $dept->description }}</div>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button onclick="document.getElementById('edit-dept-{{ $dept->id }}').classList.toggle('hidden')"
                    class="text-xs text-slate-500 hover:text-violet-600 font-medium px-2 py-1 rounded hover:bg-slate-100 transition">
                    Edit
                </button>
                <form method="POST" action="{{ route('superadmin.departments.destroy', $dept) }}"
                    onsubmit="return confirm('Hapus department {{ $dept->name }}? Tidak bisa dibatalkan.')"
                    class="inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-400 hover:text-red-600 font-medium px-2 py-1 rounded hover:bg-red-50 transition">
                        Hapus
                    </button>
                </form>
            </div>
        </div>

        {{-- Stats row --}}
        <div class="grid grid-cols-5 divide-x divide-slate-100 text-center py-3">
            <div><div class="text-lg font-bold text-slate-800">{{ $dept->roles_count }}</div><div class="text-xs text-slate-400">Roles</div></div>
            <div><div class="text-lg font-bold text-slate-800">{{ $dept->categories_count }}</div><div class="text-xs text-slate-400">Kategori</div></div>
            <div><div class="text-lg font-bold text-slate-800">{{ $dept->approval_steps_count }}</div><div class="text-xs text-slate-400">Steps</div></div>
            <div><div class="text-lg font-bold text-slate-800">{{ $dept->users_count }}</div><div class="text-xs text-slate-400">Users</div></div>
            <div><div class="text-lg font-bold text-slate-800">{{ $dept->work_orders_count }}</div><div class="text-xs text-slate-400">WOs</div></div>
        </div>

        {{-- Action footer --}}
        <div class="flex gap-2 px-4 py-3 bg-slate-50 border-t border-slate-100">
            <a href="{{ route('dept-admin.index') }}?dept={{ $dept->id }}"
                class="flex-1 text-center text-sm font-medium bg-white border border-slate-300 hover:border-violet-400 hover:text-violet-700 text-slate-700 py-1.5 rounded-lg transition">
                Kelola Konfigurasi
            </a>
            @if ($dept->has_warehouse)
            <span class="text-xs text-teal-600 bg-teal-50 px-2.5 py-1.5 rounded-lg font-medium self-center">Warehouse</span>
            @endif
            @if ($dept->has_unit_structure)
            <span class="text-xs text-blue-600 bg-blue-50 px-2.5 py-1.5 rounded-lg font-medium self-center">Unit/Group</span>
            @endif
        </div>

        {{-- Edit form (hidden) --}}
        <div id="edit-dept-{{ $dept->id }}" class="hidden border-t border-violet-100 bg-violet-50 px-5 py-5">
            <h4 class="text-sm font-semibold text-slate-700 mb-3">Edit Department</h4>
            <form method="POST" action="{{ route('superadmin.departments.update', $dept) }}">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Nama *</label>
                        <input type="text" name="name" value="{{ $dept->name }}" required maxlength="100"
                            class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Kode *</label>
                        <input type="text" name="code" value="{{ $dept->code }}" required maxlength="10"
                            class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Slug *</label>
                        <input type="text" name="slug" value="{{ $dept->slug }}" required maxlength="50"
                            class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Warna *</label>
                        <select name="color" required class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                            @foreach (['blue','green','purple','red','orange','teal','slate'] as $c)
                            <option value="{{ $c }}" {{ $dept->color === $c ? 'selected' : '' }}>{{ ucfirst($c) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-slate-600 mb-1">Deskripsi</label>
                        <input type="text" name="description" value="{{ $dept->description }}" maxlength="255"
                            class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div class="flex gap-4 flex-wrap">
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ $dept->is_active ? 'checked' : '' }}
                                class="rounded border-slate-300 text-violet-600"> Aktif
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" name="has_warehouse" value="1" {{ $dept->has_warehouse ? 'checked' : '' }}
                                class="rounded border-slate-300 text-violet-600"> Warehouse
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" name="has_unit_structure" value="1" {{ $dept->has_unit_structure ? 'checked' : '' }}
                                class="rounded border-slate-300 text-violet-600"> Unit/Group
                        </label>
                    </div>
                </div>
                <div class="flex gap-3 mt-4">
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white font-semibold px-4 py-1.5 rounded-lg text-sm transition">
                        Simpan Perubahan
                    </button>
                    <button type="button"
                        onclick="document.getElementById('edit-dept-{{ $dept->id }}').classList.add('hidden')"
                        class="text-sm text-slate-500 hover:text-slate-700">Batal</button>
                </div>
            </form>
        </div>
    </div>
    @endforeach
</div>

@if ($departments->isEmpty())
<div class="bg-white rounded-xl shadow-sm p-10 text-center text-slate-400">
    Belum ada department. Klik "Tambah Department" untuk membuat yang pertama.
</div>
@endif

@endsection
