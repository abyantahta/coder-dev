@php
$isQaScope = auth()->user()->isQaSectionHead();
$departments = $isQaScope ? ['QA'] : ['IT','GA','Engineering','Maintenance','Admin','HRGA','QC','PPIC','Produksi'];
@endphp

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama <span class="text-red-500">*</span></label>
    <input type="text" name="name" value="{{ old('name', $user?->name) }}" required
        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-400 @enderror">
    @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email <span class="text-red-500">*</span></label>
    <input type="email" name="email" value="{{ old('email', $user?->email) }}" required
        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-400 @enderror">
    @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1.5">
        Password {{ $user ? '(kosongkan jika tidak ingin mengubah)' : '*' }}
    </label>
    <input type="password" name="password" {{ $user ? '' : 'required' }}
        class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-400 @enderror"
        placeholder="{{ $user ? '••••••••' : 'Min. 6 karakter' }}">
    @error('password') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Role <span class="text-red-500">*</span></label>
        <select name="role" id="role-select" required
            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @foreach ($roles as $r)
            <option value="{{ $r }}" {{ old('role', $user?->role) === $r ? 'selected' : '' }}>
                {{ \App\Models\User::roleLabel($r) }}
            </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Departemen <span class="text-red-500">*</span></label>
        <select name="department" required
            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @foreach ($departments as $dept)
            <option value="{{ $dept }}" {{ old('department', $user?->department) === $dept ? 'selected' : '' }}>{{ $dept }}</option>
            @endforeach
        </select>
    </div>
</div>

{{-- Maintenance-specific fields --}}
<div id="maintenance-fields">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Unit</label>
        <select name="unit_id" id="unit-select"
            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">— Tidak ada —</option>
            @foreach ($units as $unit)
            <option value="{{ $unit->id }}" {{ old('unit_id', $user?->unit_id) == $unit->id ? 'selected' : '' }}>
                {{ $unit->name }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="mt-4">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Group</label>
        <select name="group_id" id="group-select"
            class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">— Tidak ada —</option>
            @foreach ($units as $unit)
                @foreach ($unit->groups as $group)
                <option value="{{ $group->id }}"
                    data-unit="{{ $unit->id }}"
                    {{ old('group_id', $user?->group_id) == $group->id ? 'selected' : '' }}>
                    {{ $group->name }} ({{ $unit->name }})
                </option>
                @endforeach
            @endforeach
        </select>
    </div>
</div>

@push('scripts')
<script>
const roleSelect = document.getElementById('role-select');
const unitSelect = document.getElementById('unit-select');
const groupSelect = document.getElementById('group-select');
const maintFields = document.getElementById('maintenance-fields');

function toggleMaintenanceFields() {
    const role = roleSelect.value;
    const show = ['unit_head','group_head','member','section_head'].includes(role);
    maintFields.style.display = show ? 'block' : 'none';
}

function filterGroups() {
    const unitId = unitSelect.value;
    Array.from(groupSelect.options).forEach(opt => {
        if (!opt.value) return;
        opt.style.display = (!unitId || opt.dataset.unit === unitId) ? '' : 'none';
    });
}

roleSelect.addEventListener('change', toggleMaintenanceFields);
unitSelect.addEventListener('change', filterGroups);
toggleMaintenanceFields();
filterGroups();
</script>
@endpush
