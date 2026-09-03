@extends('layouts.app')
@section('title', 'Buat Work Order')
@section('page-title', 'Buat Work Order Baru')

@section('content')

<div class="max-w-2xl">
    <div class="bg-white rounded-xl shadow-sm p-6">

        <form method="POST" action="{{ route('work-orders.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- Judul --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Judul WO <span class="text-red-500">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title') border-red-400 @enderror"
                    placeholder="Contoh: Perbaikan mesin press line 3">
                @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Deskripsi --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Deskripsi Pekerjaan <span class="text-red-500">*</span></label>
                <textarea name="description" rows="4" required
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-400 @enderror"
                    placeholder="Jelaskan masalah dan pekerjaan yang dibutuhkan secara detail…">{{ old('description') }}</textarea>
                @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Tujuan & Prioritas --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kirim Ke (Departemen) <span class="text-red-500">*</span></label>
                    <select name="target_department_id" id="dept-select" required
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('target_department_id') border-red-400 @enderror">
                        <option value="">— Pilih Departemen —</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('target_department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }} ({{ $dept->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('target_department_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Prioritas <span class="text-red-500">*</span></label>
                    <select name="priority" required
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="low"    {{ old('priority') === 'low'    ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high"   {{ old('priority') === 'high'   ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>
            </div>

            {{-- WO Category (dept-specific) --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Kategori WO <span class="text-red-500">*</span></label>
                <select name="wo_category_id" id="category-select" required
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('wo_category_id') border-red-400 @enderror"
                    disabled>
                    <option value="">— Pilih departemen terlebih dahulu —</option>
                </select>
                @error('wo_category_id') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror

                {{-- Leadtime badge --}}
                <div id="leadtime-info" class="mt-2 hidden">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">Leadtime:</span>
                        <span id="leadtime-badge" class="text-xs font-semibold bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full"></span>
                        <span id="category-desc" class="text-xs text-slate-500 italic"></span>
                    </div>
                </div>
            </div>

            {{-- Tipe Pekerjaan (Electrical/Mechanical) --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Tipe Pekerjaan</label>
                <input type="text" name="category" value="{{ old('category') }}"
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Contoh: Electrical, Mechanical, Civil…">
            </div>

            {{-- Lampiran --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Lampiran (opsional)</label>
                <input type="file" name="attachment" accept=".pdf,.png"
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('attachment') border-red-400 @enderror">
                <p class="text-xs text-slate-400 mt-1">Format PDF atau PNG, maksimal 5MB. Tidak wajib diisi.</p>
                @error('attachment') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Info notice --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                <strong>Info:</strong> Setelah WO dibuat, departemen yang dituju akan menerima dan menindaklanjuti sesuai leadtime kategori.
                Kamu akan bisa melakukan review ketika pekerjaan selesai.
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-lg transition text-sm">
                    Kirim Work Order
                </button>
                <a href="{{ route('dashboard') }}" class="text-sm text-slate-500 hover:text-slate-700">Batal</a>
            </div>
        </form>

    </div>
</div>

@endsection

@push('scripts')
<script>
const categoriesByDept = @json($categoriesByDept);
const oldDeptId  = {{ old('target_department_id', 'null') }};
const oldCatId   = {{ old('wo_category_id', 'null') }};

const deptSelect = document.getElementById('dept-select');
const catSelect  = document.getElementById('category-select');
const leadtimeInfo  = document.getElementById('leadtime-info');
const leadtimeBadge = document.getElementById('leadtime-badge');
const categoryDesc  = document.getElementById('category-desc');

function updateCategories(deptId) {
    catSelect.innerHTML = '';
    if (!deptId || !categoriesByDept[deptId]) {
        catSelect.innerHTML = '<option value="">— Pilih departemen terlebih dahulu —</option>';
        catSelect.disabled = true;
        leadtimeInfo.classList.add('hidden');
        return;
    }

    const cats = categoriesByDept[deptId];
    catSelect.disabled = false;
    catSelect.innerHTML = '<option value="">— Pilih Kategori WO —</option>';
    cats.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.name + ' (' + c.leadtime_days + ' HK)';
        opt.dataset.leadtime = c.leadtime_days;
        opt.dataset.desc     = c.description || '';
        if (oldCatId && c.id == oldCatId) opt.selected = true;
        catSelect.appendChild(opt);
    });

    updateLeadtime();
}

function updateLeadtime() {
    const selected = catSelect.options[catSelect.selectedIndex];
    if (selected && selected.dataset.leadtime) {
        const days = selected.dataset.leadtime;
        leadtimeBadge.textContent = days + ' Hari Kerja';
        categoryDesc.textContent  = selected.dataset.desc;
        leadtimeInfo.classList.remove('hidden');
    } else {
        leadtimeInfo.classList.add('hidden');
    }
}

deptSelect.addEventListener('change', () => updateCategories(deptSelect.value));
catSelect.addEventListener('change', updateLeadtime);

// Restore old values on validation error
if (oldDeptId) {
    updateCategories(oldDeptId);
}
</script>
@endpush
