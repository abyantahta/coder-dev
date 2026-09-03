@extends('layouts.app')
@section('title', 'Alur Approval — ' . $dept->name)
@section('page-title', 'Alur Approval — ' . $dept->name)

@section('content')

@if (session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
@endif

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('dept-admin.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke Dept Admin</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- Steps list (sortable) --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Urutan Approval</h3>
            <span class="text-xs text-slate-400">Drag untuk mengurutkan</span>
        </div>

        <ol id="steps-list" class="divide-y divide-slate-100">
            @foreach ($steps as $step)
            <li class="px-5 py-3 flex items-start gap-3 cursor-grab" data-id="{{ $step->id }}">
                <span class="mt-0.5 w-7 h-7 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold shrink-0 step-num">{{ $step->step_order }}</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="font-medium text-slate-800 text-sm">{{ $step->name }}</span>
                        <span class="text-xs bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">{{ $step->step_type }}</span>
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        Actor: {{ $step->actorRole?->name ?? ('(by step type: ' . $step->step_type . ')') }}
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5 flex gap-2 flex-wrap">
                        @if ($step->can_reject)  <span>✕ reject</span> @endif
                        @if ($step->can_forward) <span>→ forward</span> @endif
                        @if ($step->can_assign)  <span>↳ assign ke {{ $step->assigns_to_role_key }}</span> @endif
                        @if ($step->auto_advance_hours) <span>⏱ auto {{ $step->auto_advance_hours }}h</span> @endif
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button onclick="openEditStep({{ $step->id }}, {{ $step->toJson() }})"
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Edit</button>
                    <form method="POST" action="{{ route('dept-admin.steps.destroy', $step) }}"
                        onsubmit="return confirm('Hapus step ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                    </form>
                </div>
            </li>
            @endforeach
        </ol>

        @if ($steps->isEmpty())
        <div class="p-8 text-center text-slate-400 text-sm">Belum ada approval step.</div>
        @endif
    </div>

    {{-- Add / Edit Step Form --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4" id="step-form-title">Tambah Step Baru</h3>
        <form method="POST" id="step-form" action="{{ route('dept-admin.steps.store') }}" class="space-y-4">
            @csrf
            <span id="step-method-field"></span>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Urutan (step_order) <span class="text-red-500">*</span></label>
                    <input type="number" name="step_order" id="step-order" required min="1" value="{{ $steps->count() + 1 }}"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Nama Step <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="step-name" required
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Contoh: Penerimaan WO">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tipe Step <span class="text-red-500">*</span></label>
                <select name="step_type" id="step-type" required
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="standard">standard — terima/tolak/forward</option>
                    <option value="spare_parts_check">spare_parts_check — cek sparepart MTC</option>
                    <option value="assign">assign — pilih user yang ditugaskan</option>
                    <option value="completion">completion — user selesai mengerjakan</option>
                    <option value="requester_review">requester_review — requester approve/rework</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Actor Role</label>
                <select name="actor_role_id" id="step-actor"
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Ditentukan oleh step type —</option>
                    @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-400 mt-1">Kosongkan untuk completion dan requester_review.</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Label Aksi <span class="text-red-500">*</span></label>
                    <input type="text" name="action_label" id="step-action-label" required value="Proses"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Label Reject</label>
                    <input type="text" name="reject_label" id="step-reject-label"
                        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        placeholder="Tolak WO">
                </div>
            </div>

            <div id="assign-field-wrap">
                <label class="block text-sm font-medium text-slate-700 mb-1">Assign ke Role Key (jika step_type=assign)</label>
                <input type="text" name="assigns_to_role_key" id="step-assigns-key"
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="group_head">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Auto-advance (jam, opsional)</label>
                <input type="number" name="auto_advance_hours" id="step-auto-hours" min="1" max="720"
                    class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    placeholder="48">
                <p class="text-xs text-slate-400 mt-1">Otomatis lanjut jika tidak ada aksi dalam N jam.</p>
            </div>

            <div class="flex items-center gap-4 flex-wrap">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="can_reject" id="step-can-reject" value="1" class="rounded"> Bisa Reject
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="can_forward" id="step-can-forward" value="1" class="rounded"> Bisa Forward
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="can_assign" id="step-can-assign" value="1" class="rounded"> Bisa Assign
                </label>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold px-5 py-2 text-sm transition">
                    Simpan Step
                </button>
                <button type="button" onclick="resetStepForm()"
                    class="text-sm text-slate-500 hover:text-slate-700">Reset</button>
            </div>
        </form>
    </div>

</div>

@endsection

@push('scripts')
<script>
function openEditStep(id, step) {
    document.getElementById('step-form-title').textContent = 'Edit Step';
    document.getElementById('step-form').action = '/dept-admin/approval-steps/' + id;
    document.getElementById('step-method-field').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('step-order').value          = step.step_order;
    document.getElementById('step-name').value           = step.name;
    document.getElementById('step-type').value           = step.step_type;
    document.getElementById('step-actor').value          = step.actor_role_id || '';
    document.getElementById('step-action-label').value   = step.action_label;
    document.getElementById('step-reject-label').value   = step.reject_label || '';
    document.getElementById('step-assigns-key').value    = step.assigns_to_role_key || '';
    document.getElementById('step-auto-hours').value     = step.auto_advance_hours || '';
    document.getElementById('step-can-reject').checked   = !!step.can_reject;
    document.getElementById('step-can-forward').checked  = !!step.can_forward;
    document.getElementById('step-can-assign').checked   = !!step.can_assign;
    window.scrollTo(0, document.getElementById('step-form').getBoundingClientRect().top + window.scrollY - 80);
}

function resetStepForm() {
    document.getElementById('step-form-title').textContent = 'Tambah Step Baru';
    document.getElementById('step-form').action = '{{ route('dept-admin.steps.store') }}';
    document.getElementById('step-method-field').innerHTML = '';
    document.getElementById('step-form').reset();
}

// Drag-to-reorder
const list = document.getElementById('steps-list');
if (list && list.children.length > 1) {
    let dragging = null;

    list.addEventListener('dragstart', e => {
        dragging = e.target.closest('li');
        dragging.style.opacity = '0.5';
    });
    list.addEventListener('dragend', e => {
        dragging.style.opacity = '';
        dragging = null;
        saveOrder();
    });
    list.addEventListener('dragover', e => {
        e.preventDefault();
        const li = e.target.closest('li');
        if (li && li !== dragging) {
            const rect = li.getBoundingClientRect();
            const after = e.clientY > rect.top + rect.height / 2;
            list.insertBefore(dragging, after ? li.nextSibling : li);
        }
    });

    Array.from(list.children).forEach(li => li.setAttribute('draggable', 'true'));

    function saveOrder() {
        const order = Array.from(list.children).map(li => li.dataset.id);
        fetch('{{ route('dept-admin.steps.reorder') }}', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ order })
        }).then(r => r.json()).then(() => {
            Array.from(list.querySelectorAll('.step-num')).forEach((el, i) => el.textContent = i + 1);
        });
    }
}
</script>
@endpush
