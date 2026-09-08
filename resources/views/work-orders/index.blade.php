@extends('layouts.app')
@section('title', 'Work Orders')
@section('page-title', 'Daftar Work Order')

@section('content')
@php $user = auth()->user(); @endphp

{{-- Header + Create button --}}
<div class="flex items-center justify-between mb-4">
    <div class="text-sm text-slate-500">{{ $wos->total() }} WO ditemukan</div>
    @if (!$user->isMaintenanceStaff())
    <a href="{{ route('work-orders.create') }}"
        class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Buat WO
    </a>
    @endif
</div>

{{-- Filters --}}
<form method="GET" class="bg-white rounded-xl shadow-sm p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Cari</label>
        <input type="text" name="search" value="{{ request('search') }}"
            placeholder="Judul / No. WO…"
            class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-48">
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
        <select name="status" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Semua Status</option>
            @foreach (['pending','accepted','rejected','forwarded_ga','forwarded_qa','pending_parts','parts_ordered','parts_received','assigned_group','assigned_member','completed','rework','finished','cancelled'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                {{ \App\Models\WorkOrder::statusLabel($s) }}
            </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Prioritas</label>
        <select name="priority" class="border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">Semua Prioritas</option>
            @foreach (['low','medium','high','urgent'] as $p)
            <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit"
        class="bg-slate-700 hover:bg-slate-800 text-white text-sm px-4 py-1.5 rounded-lg transition">Filter</button>
    @if (request()->hasAny(['search','status','priority']))
    <a href="{{ route('work-orders.index') }}"
        class="text-sm text-slate-500 hover:text-slate-700 py-1.5">Reset</a>
    @endif
</form>

{{-- ═══ Tabel WO Sendiri ═══ --}}
@php
    $ownLabel = $user->isUnitHead()
        ? ($user->unit?->name ?? 'Unit Saya')
        : ($user->isGroupHead() ? ($user->group?->name ?? 'Group Saya') : null);
@endphp

@if ($ownLabel)
<div class="flex items-center gap-3 mb-2">
    <h2 class="text-sm font-semibold text-slate-700">WO {{ $ownLabel }}</h2>
    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ $wos->total() }} WO</span>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">No. WO</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Judul</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Requester</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Prioritas</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Status</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Assigned</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Deadline</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($wos as $wo)
                <tr class="hover:bg-slate-50 transition cursor-pointer {{ $wo->isOverdue() ? 'bg-red-50/50 shadow-[inset_3px_0_0_0_var(--color-red-500)]' : '' }}"
                    onclick="window.location='{{ route('work-orders.show', $wo) }}'"
                    title="{{ $wo->title }}">
                    <td class="px-4 py-3 font-mono text-xs text-slate-600 whitespace-nowrap">{{ $wo->wo_number }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800 max-w-48 truncate">{{ $wo->title }}</div>
                        @if ($wo->unit) <div class="text-xs text-slate-400">{{ $wo->unit->name }}</div> @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="text-slate-700">{{ $wo->requester->name }}</div>
                        <div class="text-xs text-slate-400">{{ $wo->requester->department }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::priorityColor($wo->priority) }}">
                            {{ ucfirst($wo->priority) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::statusColor($wo->status) }}">
                            {{ \App\Models\WorkOrder::statusLabel($wo->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600 text-xs">
                        {{ $wo->assignedMember?->name ?? ($wo->assignedGroup?->name ?? '—') }}
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        @if ($wo->deadline)
                            <span class="{{ $wo->isOverdue() ? 'text-red-600 font-semibold' : 'text-slate-500' }} text-xs">
                                {{ $wo->deadline->format('d M Y') }}
                            </span>
                            @if ($wo->isOverdue())
                            <span class="ml-1 text-xs text-red-500">(Overdue)</span>
                            @endif
                        @else
                        <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('work-orders.show', $wo) }}"
                            class="text-blue-600 hover:text-blue-800 text-xs font-medium">Detail →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-slate-400 py-10">
                        <svg class="w-10 h-10 text-slate-200 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Tidak ada work order ditemukan.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($wos->hasPages())
    <div class="px-4 py-3 border-t border-slate-100">
        {{ $wos->links() }}
    </div>
    @endif
</div>

{{-- ═══ Tabel WO Unit/Group Lain (read-only) ═══ --}}
@if (!is_null($otherWos))
<div class="flex items-center gap-3 mb-2 mt-2">
    <h2 class="text-sm font-semibold text-slate-700">WO Unit / Group Lain</h2>
    <span class="text-xs px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">{{ $otherWos->total() }} WO</span>
    <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-medium">Read Only</span>
</div>

<div class="bg-white rounded-xl shadow-sm overflow-hidden border border-slate-200 opacity-90">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">No. WO</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Judul</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Unit / Group</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Requester</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Prioritas</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Status</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Deadline</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($otherWos as $wo)
                <tr class="hover:bg-slate-50 transition text-slate-500 cursor-pointer"
                    onclick="window.location='{{ route('work-orders.show', $wo) }}'"
                    title="{{ $wo->title }}">
                    <td class="px-4 py-3 font-mono text-xs whitespace-nowrap">{{ $wo->wo_number }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium max-w-48 truncate">{{ $wo->title }}</div>
                    </td>
                    <td class="px-4 py-3 text-xs">
                        @if ($user->isUnitHead())
                            <span class="text-slate-600">{{ $wo->unit?->name ?? '—' }}</span>
                        @else
                            <span class="text-slate-600">{{ $wo->assignedGroup?->name ?? '—' }}</span>
                            @if ($wo->unit) <div class="text-slate-400">{{ $wo->unit->name }}</div> @endif
                        @endif
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div>{{ $wo->requester->name }}</div>
                        <div class="text-xs text-slate-400">{{ $wo->requester->department }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::priorityColor($wo->priority) }}">
                            {{ ucfirst($wo->priority) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::statusColor($wo->status) }}">
                            {{ \App\Models\WorkOrder::statusLabel($wo->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs">
                        @if ($wo->deadline)
                            {{ $wo->deadline->format('d M Y') }}
                        @else
                            <span class="text-slate-300">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('work-orders.show', $wo) }}"
                            class="text-slate-400 hover:text-slate-600 text-xs font-medium">Lihat →</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-slate-400 py-8 text-sm">
                        Tidak ada WO dari unit/group lain.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($otherWos->hasPages())
    <div class="px-4 py-3 border-t border-slate-100">
        {{ $otherWos->links() }}
    </div>
    @endif
</div>
@endif

@endsection
