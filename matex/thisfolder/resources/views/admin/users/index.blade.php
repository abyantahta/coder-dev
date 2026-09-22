@extends('layouts.app')
@section('title', 'Manajemen User')
@section('page-title', 'Manajemen User')

@section('content')

<div class="flex items-center justify-between mb-4">
    <div class="text-sm text-slate-500">{{ $users->count() }} user terdaftar</div>
    <a href="{{ route('admin.users.create') }}"
        class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah User
    </a>
</div>

{{-- Unit/Group Overview --}}
@if ($units->isNotEmpty())
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    @foreach ($units as $unit)
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="font-semibold text-slate-800 mb-2">{{ $unit->name }}</div>
        @foreach ($unit->groups as $group)
        <div class="text-xs bg-slate-50 border border-slate-100 rounded p-2 mb-1">
            <span class="font-medium text-slate-700">{{ $group->name }}</span>
            <span class="text-slate-400 ml-1">({{ $group->members()->count() }} member)</span>
        </div>
        @endforeach
    </div>
    @endforeach
</div>
@endif

<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Nama</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Email</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Role</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Departemen</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Unit / Group</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">SR</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($users as $u)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-violet-200 to-fuchsia-200 rounded-full flex items-center justify-center text-violet-700 text-xs font-bold shrink-0">
                                {{ strtoupper(substr($u->name, 0, 2)) }}
                            </div>
                            <span class="font-medium text-slate-800">{{ $u->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-slate-600 text-xs">{{ $u->email }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ match($u->role) {
                            'section_head' => 'bg-red-100 text-red-700',
                            'unit_head' => 'bg-orange-100 text-orange-700',
                            'group_head' => 'bg-yellow-100 text-yellow-700',
                            'member' => 'bg-blue-100 text-blue-700',
                            default => 'bg-slate-100 text-slate-600',
                        } }}">
                            {{ \App\Models\User::roleLabel($u->role) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 text-xs">{{ $u->department }}</td>
                    <td class="px-4 py-3 text-slate-600 text-xs">
                        {{ $u->unit?->name ?? '—' }}
                        @if ($u->group) / {{ $u->group->name }} @endif
                    </td>
                    <td class="px-4 py-3">
                        @if ($u->service_rate > 0)
                        <span class="text-sm font-bold {{ $u->service_rate >= 80 ? 'text-green-600' : ($u->service_rate >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $u->service_rate }}%
                        </span>
                        @else
                        <span class="text-slate-400 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('admin.users.edit', $u) }}"
                                class="text-xs text-violet-600 hover:text-violet-800 font-medium">Edit</a>
                            @if ($u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}"
                                onsubmit="return confirm('Hapus user {{ $u->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">Hapus</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@endsection
