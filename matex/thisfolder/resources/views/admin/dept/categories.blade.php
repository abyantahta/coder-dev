@extends('layouts.app')
@section('title', 'Kategori WO — ' . $dept->name)
@section('page-title', 'Kategori WO — ' . $dept->name)

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

    {{-- Category List --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">Daftar Kategori ({{ $cats->count() }})</h3>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($cats as $cat)
            <div class="px-5 py-4 flex items-start justify-between gap-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <span class="font-medium text-slate-800">{{ $cat->name }}</span>
                        <span class="text-xs font-semibold bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">{{ $cat->leadtime_days }} HK</span>
                        @if (!$cat->is_active)
                            <span class="text-xs bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">Nonaktif</span>
                        @endif
                    </div>
                    @if ($cat->description)
                        <p class="text-xs text-slate-500 mt-0.5">{{ $cat->description }}</p>
                    @endif
                    <p class="text-xs text-slate-400 mt-0.5">{{ $cat->work_orders_count }} WO · sort {{ $cat->sort_order }}</p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button onclick="openEditCat({{ $cat->id }}, {{ $cat->toJson() }})"
                        class="text-xs text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                    @if ($cat->work_orders_count == 0)
                    <form method="POST" action="{{ route('dept-admin.categories.destroy', $cat) }}"
                        onsubmit="return confirm('Hapus kategori {{ $cat->name }}?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                    </form>
                    @endif
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-slate-400 text-sm">Belum ada kategori.</div>
            @endforelse
        </div>
    </div>

    {{-- Add / Edit Category Form --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4" id="cat-form-title">Tambah Kategori Baru</h3>
        <form method="POST" id="cat-form" action="{{ route('dept-admin.categories.store') }}" class="space-y-4">
            @csrf
            <span id="cat-method-field"></span>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Nama Kategori <span class="text-red-500">*</span></label>
                <input type="text" name="name" id="cat-name" required
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Contoh: Safety, Productivity">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi</label>
                <input type="text" name="description" id="cat-desc"
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Keterangan singkat…">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Leadtime (Hari Kerja) <span class="text-red-500">*</span></label>
                    <input type="number" name="leadtime_days" id="cat-leadtime" required min="1" max="90" value="1"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sort Order <span class="text-red-500">*</span></label>
                    <input type="number" name="sort_order" id="cat-sort" required min="0" value="{{ $cats->count() + 1 }}"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Warna Badge <span class="text-red-500">*</span></label>
                <select name="color" id="cat-color" required
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="slate">Slate (abu)</option>
                    <option value="blue">Blue (biru)</option>
                    <option value="green">Green (hijau)</option>
                    <option value="yellow">Yellow (kuning)</option>
                    <option value="orange">Orange (oranye)</option>
                    <option value="red">Red (merah)</option>
                    <option value="purple">Purple (ungu)</option>
                </select>
            </div>

            <div id="cat-active-wrap" style="display:none">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="is_active" id="cat-active" value="1" checked class="rounded">
                    Aktif
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold px-5 py-2 text-sm transition">
                    Simpan Kategori
                </button>
                <button type="button" onclick="resetCatForm()"
                    class="text-sm text-slate-500 hover:text-slate-700">Reset</button>
            </div>
        </form>
    </div>

</div>

@endsection

@push('scripts')
<script>
function openEditCat(id, cat) {
    document.getElementById('cat-form-title').textContent = 'Edit Kategori';
    document.getElementById('cat-form').action = '/dept-admin/categories/' + id;
    document.getElementById('cat-method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('cat-name').value      = cat.name;
    document.getElementById('cat-desc').value      = cat.description || '';
    document.getElementById('cat-leadtime').value  = cat.leadtime_days;
    document.getElementById('cat-sort').value      = cat.sort_order;
    document.getElementById('cat-color').value     = cat.color;
    document.getElementById('cat-active').checked  = !!cat.is_active;
    document.getElementById('cat-active-wrap').style.display = '';
}

function resetCatForm() {
    document.getElementById('cat-form-title').textContent = 'Tambah Kategori Baru';
    document.getElementById('cat-form').action = '{{ route('dept-admin.categories.store') }}';
    document.getElementById('cat-method-field').innerHTML = '';
    document.getElementById('cat-form').reset();
    document.getElementById('cat-active-wrap').style.display = 'none';
}
</script>
@endpush
