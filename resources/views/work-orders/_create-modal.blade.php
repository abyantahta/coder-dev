@php
    $openCreateWo = $errors->hasAny([
            'title', 'description', 'target_department_id', 'wo_category_id', 'attachment', 'category',
        ])
        || request()->boolean('buat')
        || (bool) session('open_create_wo');
    $departments = $woCreateDepartments ?? collect();
    $categoriesByDept = $woCreateCategoriesByDept ?? collect();
@endphp

<style>
    #wo-create-modal {
        position: fixed;
        inset: 0;
        z-index: 99999;
    }
    #wo-create-backdrop {
        position: absolute;
        inset: 0;
        background: rgb(18 22 28 / .48);
        opacity: 0;
        transition: opacity .22s cubic-bezier(.4, 0, .2, 1);
    }
    #wo-create-panel {
        pointer-events: auto;
        max-height: min(90vh, 880px);
        opacity: 0;
        transform: translateY(18px) scale(.96);
        transition:
            opacity .22s cubic-bezier(.4, 0, .2, 1),
            transform .32s cubic-bezier(.16, 1, .3, 1);
        will-change: opacity, transform;
    }
    #wo-create-modal.is-open #wo-create-backdrop { opacity: 1; }
    #wo-create-modal.is-open #wo-create-panel {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    @media (prefers-reduced-motion: reduce) {
        #wo-create-backdrop,
        #wo-create-panel { transition: none; }
    }
</style>

<div id="wo-create-modal"
    class="{{ $openCreateWo ? 'is-open' : 'hidden' }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="wo-create-title"
    data-open="{{ $openCreateWo ? '1' : '0' }}">
    <div id="wo-create-backdrop" data-wo-create-close></div>
    <div class="flex items-start justify-center p-4 sm:items-center sm:p-6"
        style="position:relative;z-index:1;min-height:100%;pointer-events:none;">
        <div id="wo-create-panel" class="w-full max-w-2xl rounded-xl bg-white shadow-lg overflow-y-auto">
            <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
                <h2 id="wo-create-title" class="text-base font-semibold text-slate-900">Buat Work Order Baru</h2>
                <button type="button" data-wo-create-close
                    class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    title="Tutup">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('work-orders.store') }}" enctype="multipart/form-data" class="space-y-5 p-6">
                @csrf

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Judul WO <span class="text-red-500">*</span></label>
                    <input type="text" name="title" id="wo-create-title-input" value="{{ old('title') }}" required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title') border-red-400 @enderror"
                        placeholder="Contoh: Perbaikan mesin press line 3">
                    @error('title') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Deskripsi Pekerjaan <span class="text-red-500">*</span></label>
                    <textarea name="description" rows="4" required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-400 @enderror"
                        placeholder="Jelaskan masalah dan pekerjaan yang dibutuhkan secara detail…">{{ old('description') }}</textarea>
                    @error('description') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Kirim Ke (Departemen) <span class="text-red-500">*</span></label>
                    <select name="target_department_id" id="wo-create-dept" required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('target_department_id') border-red-400 @enderror">
                        <option value="">— Pilih Departemen —</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" {{ (string) old('target_department_id') === (string) $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }} ({{ $dept->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('target_department_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Kategori WO <span class="text-red-500">*</span></label>
                    <select name="wo_category_id" id="wo-create-category" required
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('wo_category_id') border-red-400 @enderror"
                        disabled>
                        <option value="">— Pilih departemen terlebih dahulu —</option>
                    </select>
                    @error('wo_category_id') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Tipe Pekerjaan</label>
                    <input type="text" name="category" value="{{ old('category') }}"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Contoh: Electrical, Mechanical, Civil…">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Lampiran (opsional)</label>
                    <input type="file" name="attachment" accept=".pdf,.png"
                        class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('attachment') border-red-400 @enderror">
                    <p class="mt-1 text-xs text-slate-400">Format PDF atau PNG, maksimal 5MB. Tidak wajib diisi.</p>
                    @error('attachment') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                    <strong>Info:</strong> Setelah WO dibuat, departemen yang dituju akan menerima dan menindaklanjuti.
                    Kamu akan bisa melakukan review ketika pekerjaan selesai.
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700">
                        Kirim Work Order
                    </button>
                    <button type="button" data-wo-create-close class="text-sm text-slate-500 hover:text-slate-700">Batal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('wo-create-modal');
    if (!modal) return;

    const categoriesByDept = @json($categoriesByDept);
    const oldDeptId = {{ old('target_department_id', 'null') }};
    const oldCatId = {{ old('wo_category_id', 'null') }};
    const deptSelect = document.getElementById('wo-create-dept');
    const catSelect = document.getElementById('wo-create-category');
    const titleInput = document.getElementById('wo-create-title-input');
    const navCreate = document.getElementById('nav-wo-create');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let closeTimer = null;

    function setNavActive(on) {
        if (!navCreate) return;
        navCreate.classList.toggle('nav-item-active', on);
        navCreate.classList.toggle('bg-ember', on);
        navCreate.classList.toggle('text-white', on);
        navCreate.classList.toggle('shadow-sm', on);
    }

    function updateCategories(deptId, selectedId) {
        catSelect.innerHTML = '';
        if (!deptId || !categoriesByDept[deptId]) {
            catSelect.innerHTML = '<option value="">— Pilih departemen terlebih dahulu —</option>';
            catSelect.disabled = true;
            return;
        }
        const cats = categoriesByDept[deptId];
        catSelect.disabled = false;
        catSelect.innerHTML = '<option value="">— Pilih Kategori WO —</option>';
        cats.forEach(function (c) {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.name;
            if (selectedId && String(c.id) === String(selectedId)) opt.selected = true;
            catSelect.appendChild(opt);
        });
    }

    function openModal() {
        if (closeTimer) {
            clearTimeout(closeTimer);
            closeTimer = null;
        }
        const alreadyOpen = modal.classList.contains('is-open') && !modal.classList.contains('hidden');
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        setNavActive(true);
        if (alreadyOpen || reduceMotion) {
            modal.classList.add('is-open');
            titleInput && titleInput.focus();
            return;
        }
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                modal.classList.add('is-open');
            });
        });
        setTimeout(function () { titleInput && titleInput.focus(); }, 180);
    }

    function closeModal() {
        if (modal.classList.contains('hidden')) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        setNavActive(false);
        const finish = function () {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            closeTimer = null;
        };
        if (reduceMotion) {
            finish();
            return;
        }
        closeTimer = setTimeout(finish, 260);
    }

    deptSelect.addEventListener('change', function () {
        updateCategories(deptSelect.value, null);
    });

    document.querySelectorAll('.js-open-wo-create').forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            openModal();
        });
    });

    modal.querySelectorAll('[data-wo-create-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });

    if (oldDeptId) updateCategories(oldDeptId, oldCatId);

    if (modal.dataset.open === '1') openModal();
})();
</script>
