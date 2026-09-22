@extends('layouts.app')
@section('title', 'Unit & Group')
@section('page-title', 'Manajemen Unit & Group')

@section('content')
<div class="grid grid-cols-1 gap-6">

    {{-- Create Unit --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Tambah Unit Baru</h3>
        <form method="POST" action="{{ route('admin.units.store') }}" class="flex gap-3 items-end">
            @csrf
            <div class="flex-1">
                <label class="block text-xs font-medium text-slate-600 mb-1">Nama Unit</label>
                <input type="text" name="name" required placeholder="Contoh: Unit Elektrik"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-medium text-slate-600 mb-1">Deskripsi</label>
                <input type="text" name="description" placeholder="Opsional"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold px-5 py-2 transition">
                Tambah
            </button>
        </form>
    </div>

    {{-- Units List --}}
    @forelse ($units as $unit)
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="font-semibold text-slate-800">{{ $unit->name }}</h3>
                @if ($unit->description)
                <p class="text-xs text-slate-500 mt-0.5">{{ $unit->description }}</p>
                @endif
                <p class="text-xs text-slate-400 mt-0.5">
                    {{ $unit->users()->count() }} user · {{ $unit->groups->count() }} group
                </p>
            </div>
            <form method="POST" action="{{ route('admin.units.destroy', $unit) }}"
                onsubmit="return confirm('Hapus unit {{ $unit->name }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus Unit</button>
            </form>
        </div>

        {{-- Groups in this unit --}}
        <div class="space-y-2 mb-4">
            @foreach ($unit->groups as $group)
            <div class="flex items-center justify-between bg-slate-50 border border-slate-100 rounded-lg px-4 py-2.5">
                <div>
                    <span class="text-sm font-medium text-slate-700">{{ $group->name }}</span>
                    <span class="text-xs text-slate-400 ml-2">{{ $group->members()->count() }} member</span>
                </div>
                <form method="POST" action="{{ route('admin.groups.destroy', $group) }}"
                    onsubmit="return confirm('Hapus group {{ $group->name }}?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                </form>
            </div>
            @endforeach
        </div>

        {{-- Add group to this unit --}}
        <form method="POST" action="{{ route('admin.units.groups.store', $unit) }}" class="flex gap-3 items-end">
            @csrf
            <div class="flex-1">
                <label class="block text-xs font-medium text-slate-600 mb-1">Tambah Group ke {{ $unit->name }}</label>
                <input type="text" name="name" required placeholder="Nama group baru"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-sm font-medium px-4 py-2 transition">
                + Group
            </button>
        </form>
    </div>
    @empty
    <div class="bg-white rounded-xl shadow-sm p-8 text-center text-slate-400">
        Belum ada unit. Tambahkan unit di atas.
    </div>
    @endforelse

</div>
@endsection
