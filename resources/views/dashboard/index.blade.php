@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php $user = auth()->user(); @endphp

{{-- ────────── SECTION HEAD ────────── --}}
@if ($user->isSectionHead())

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach ([
        ['Total WO', $totalWo, 'bg-blue-600', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
        ['Pending', $pendingWo, 'bg-yellow-500', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0'],
        ['Selesai', $finishedWo, 'bg-green-600', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0'],
        ['Overdue', $overdueWos, 'bg-red-600', 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'],
    ] as [$label, $val, $color, $icon])
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 {{ $color }} rounded-xl flex items-center justify-center shrink-0">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}" />
            </svg>
        </div>
        <div>
            <div class="text-2xl font-bold text-slate-800">{{ $val }}</div>
            <div class="text-sm text-slate-500">{{ $label }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Units Overview --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Unit Maintenance</h3>
        <div class="space-y-3">
            @foreach ($units as $unit)
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div>
                    <div class="font-medium text-slate-800 text-sm">{{ $unit->name }}</div>
                    <div class="text-xs text-slate-500">{{ $unit->groups->count() }} group · {{ $unit->members()->count() }} member</div>
                </div>
                <div class="text-right">
                    <div class="text-lg font-bold {{ $unit->service_rate >= 80 ? 'text-green-600' : ($unit->service_rate >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ $unit->service_rate }}%
                    </div>
                    <div class="text-xs text-slate-500">Service Rate</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800">WO Terbaru</h3>
            <a href="{{ route('work-orders.index') }}" class="text-xs text-blue-600 hover:underline">Lihat semua</a>
        </div>
        <div class="space-y-2">
            @forelse ($recentWos as $wo)
            <a href="{{ route('work-orders.show', $wo) }}"
                class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 transition">
                <div class="min-w-0">
                    <div class="text-sm font-medium text-slate-800 truncate">{{ $wo->title }}</div>
                    <div class="text-xs text-slate-500">{{ $wo->wo_number }} · {{ $wo->requester->name }}</div>
                </div>
                <span class="ml-3 shrink-0 text-xs px-2 py-1 rounded-full {{ \App\Models\WorkOrder::statusColor($wo->status) }}">
                    {{ \App\Models\WorkOrder::statusLabel($wo->status) }}
                </span>
            </a>
            @empty
            <p class="text-sm text-slate-400 py-4 text-center">Belum ada WO.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ────────── UNIT HEAD ────────── --}}
@elseif ($user->isUnitHead())

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach ([
        ['WO Pending', $pendingWo, 'bg-yellow-500', 'Perlu perhatian segera'],
        ['WO Aktif', $activeWo, 'bg-blue-600', 'Di unit kamu'],
        ['Selesai', $finishedWo, 'bg-green-600', 'Di unit kamu'],
        ['Overdue', $overdueWos, 'bg-red-600', 'Sudah lewat deadline'],
    ] as [$label, $val, $color, $sub])
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 {{ $color }} rounded-xl flex items-center justify-center text-white font-bold text-lg shrink-0">
            {{ $val }}
        </div>
        <div>
            <div class="text-sm font-semibold text-slate-800">{{ $label }}</div>
            <div class="text-xs text-slate-500">{{ $sub }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Pending WOs --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800">WO Masuk (Pending)</h3>
            <a href="{{ route('work-orders.index', ['status' => 'pending']) }}" class="text-xs text-blue-600 hover:underline">Lihat semua</a>
        </div>
        <div class="space-y-2">
            @forelse ($recentPending as $wo)
            <a href="{{ route('work-orders.show', $wo) }}"
                class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 transition">
                <div class="min-w-0">
                    <div class="text-sm font-medium text-slate-800 truncate">{{ $wo->title }}</div>
                    <div class="text-xs text-slate-500">{{ $wo->wo_number }} · {{ $wo->requester->name }} ({{ $wo->requester->department }})</div>
                </div>
                <span class="ml-3 shrink-0 text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::priorityColor($wo->priority) }}">
                    {{ ucfirst($wo->priority) }}
                </span>
            </a>
            @empty
            <p class="text-sm text-slate-400 py-4 text-center">Tidak ada WO pending.</p>
            @endforelse
        </div>
    </div>

    {{-- Groups SR --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Group Performance</h3>
        <div class="space-y-3">
            @forelse ($groups as $group)
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-medium text-slate-700">{{ $group->name }}</span>
                        <span class="text-sm font-bold {{ $group->service_rate >= 80 ? 'text-green-600' : ($group->service_rate >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $group->service_rate }}%
                        </span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2">
                        <div class="h-2 rounded-full {{ $group->service_rate >= 80 ? 'bg-green-500' : ($group->service_rate >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                            style="width: {{ $group->service_rate }}%"></div>
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5">{{ $group->members->count() }} member</div>
                </div>
            </div>
            @empty
            <p class="text-sm text-slate-400 text-center py-4">Belum ada group.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ────────── GROUP HEAD ────────── --}}
@elseif ($user->isGroupHead())

<div class="grid grid-cols-3 gap-4 mb-6">
    @foreach ([
        ['WO Aktif', $myGroupWos, 'bg-blue-600'],
        ['Perlu Assign', $pendingAssign, 'bg-yellow-500'],
        ['Selesai', $completedWo, 'bg-green-600'],
    ] as [$label, $val, $color])
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 {{ $color }} rounded-xl flex items-center justify-center text-white font-bold text-lg shrink-0">
            {{ $val }}
        </div>
        <div class="text-sm font-semibold text-slate-800">{{ $label }}</div>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800">WO Terbaru</h3>
            <a href="{{ route('work-orders.index') }}" class="text-xs text-blue-600 hover:underline">Lihat semua</a>
        </div>
        <div class="space-y-2">
            @forelse ($recentWos as $wo)
            <a href="{{ route('work-orders.show', $wo) }}"
                class="flex items-center justify-between p-3 rounded-lg hover:bg-slate-50 transition">
                <div class="min-w-0">
                    <div class="text-sm font-medium text-slate-800 truncate">{{ $wo->title }}</div>
                    <div class="text-xs text-slate-500">{{ $wo->wo_number }} · {{ $wo->assignedMember?->name ?? 'Belum diassign' }}</div>
                </div>
                <span class="ml-3 shrink-0 text-xs px-2 py-1 rounded-full {{ \App\Models\WorkOrder::statusColor($wo->status) }}">
                    {{ \App\Models\WorkOrder::statusLabel($wo->status) }}
                </span>
            </a>
            @empty
            <p class="text-sm text-slate-400 py-4 text-center">Belum ada WO.</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Member Saya</h3>
        <div class="space-y-3">
            @forelse ($members as $member)
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-700 text-sm font-bold">
                        {{ strtoupper(substr($member->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="text-sm font-medium text-slate-800">{{ $member->name }}</div>
                        <div class="text-xs text-slate-500">{{ $member->wo_count }} WO · {{ $member->finished_wo_count }} selesai</div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-base font-bold {{ $member->service_rate >= 80 ? 'text-green-600' : ($member->service_rate >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ $member->service_rate > 0 ? $member->service_rate . '%' : 'N/A' }}
                    </div>
                    <div class="text-xs text-slate-400">SR</div>
                </div>
            </div>
            @empty
            <p class="text-sm text-slate-400 text-center py-4">Belum ada member.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ────────── MEMBER ────────── --}}
@elseif ($user->isMember())

<div class="grid grid-cols-3 gap-4 mb-6">
    @foreach ([
        ['WO Aktif', $myActive, 'bg-blue-600'],
        ['Overdue', $overdue, 'bg-red-600'],
        ['Selesai', $myFinished, 'bg-green-600'],
    ] as [$label, $val, $color])
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 {{ $color }} rounded-xl flex items-center justify-center text-white font-bold text-lg shrink-0">
            {{ $val }}
        </div>
        <div>
            <div class="text-sm font-semibold text-slate-800">{{ $label }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- My SR --}}
<div class="bg-white rounded-xl shadow-sm p-5 mb-6 flex items-center gap-6">
    <div class="text-center">
        <div class="text-4xl font-bold {{ $user->service_rate >= 80 ? 'text-green-600' : ($user->service_rate >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
            {{ $user->service_rate > 0 ? $user->service_rate . '%' : 'N/A' }}
        </div>
        <div class="text-sm text-slate-500 mt-1">Service Rate Kamu</div>
    </div>
    <div class="flex-1">
        <div class="text-sm text-slate-600 mb-1">Berdasarkan {{ $myFinished }} WO yang selesai</div>
        <div class="w-full bg-slate-100 rounded-full h-3">
            <div class="h-3 rounded-full {{ $user->service_rate >= 80 ? 'bg-green-500' : ($user->service_rate >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                style="width: {{ min(100, $user->service_rate) }}%"></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-slate-800">WO Aktif Saya</h3>
        <a href="{{ route('work-orders.index') }}" class="text-xs text-blue-600 hover:underline">Lihat semua</a>
    </div>
    <div class="space-y-2">
        @forelse ($myWos as $wo)
        <a href="{{ route('work-orders.show', $wo) }}"
            class="flex items-center justify-between p-4 border border-slate-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition">
            <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-800 truncate">{{ $wo->title }}</div>
                <div class="text-xs text-slate-500">{{ $wo->wo_number }} · {{ $wo->requester->name }}</div>
                @if ($wo->deadline)
                <div class="text-xs mt-1 {{ $wo->isOverdue() ? 'text-red-600 font-semibold' : 'text-slate-400' }}">
                    Deadline: {{ $wo->deadline->format('d M Y') }}
                    @if ($wo->status === 'rework') · Rework deadline: {{ $wo->rework_deadline?->format('d M Y') }} @endif
                </div>
                @endif
            </div>
            <span class="ml-3 shrink-0 text-xs px-2 py-1 rounded-full {{ \App\Models\WorkOrder::statusColor($wo->status) }}">
                {{ \App\Models\WorkOrder::statusLabel($wo->status) }}
            </span>
        </a>
        @empty
        <p class="text-sm text-slate-400 py-4 text-center">Tidak ada WO aktif.</p>
        @endforelse
    </div>
</div>

{{-- ────────── WAREHOUSE MTC ────────── --}}
@elseif ($user->isWarehouseMtc())

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach ([
        ['Menunggu PR', $pendingOrders, 'bg-amber-500', 'Perlu dibuatkan PR di QAD'],
        ['PR Aktif', $activeOrders, 'bg-blue-600', 'Dalam proses pengiriman'],
        ['Diterima Bulan Ini', $receivedMonth, 'bg-green-600', 'Barang sudah tiba'],
        ['Overdue', $overdueOrders, 'bg-red-600', 'Estimasi tiba sudah lewat'],
    ] as [$label, $val, $color, $sub])
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 {{ $color }} rounded-xl flex items-center justify-center text-white font-bold text-lg shrink-0">
            {{ $val }}
        </div>
        <div>
            <div class="text-sm font-semibold text-slate-800">{{ $label }}</div>
            <div class="text-xs text-slate-500">{{ $sub }}</div>
        </div>
    </div>
    @endforeach
</div>

<div class="bg-white rounded-xl shadow-sm p-10 flex flex-col items-center justify-center gap-4">
    <svg class="w-14 h-14 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
    </svg>
    <p class="text-slate-500 text-sm">Kelola pengadaan sparepart, pembuatan PR, dan penerimaan barang.</p>
    <a href="{{ route('warehouse.index') }}"
        class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-6 py-2.5 rounded-lg transition">
        Buka Warehouse Dashboard
    </a>
</div>

{{-- ────────── QA GROUP HEAD ────────── --}}
@elseif ($user->isQaGroupHead() || $user->isQaSectionHead())

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach ([
        ['Pending QA', $pendingWo, 'bg-yellow-500'],
        ['Aktif', $activeWo, 'bg-blue-600'],
        ['Selesai', $finishedWo, 'bg-green-600'],
        ['Overdue', $overdueWos, 'bg-red-600'],
    ] as [$label, $val, $color])
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 {{ $color }} rounded-xl flex items-center justify-center text-white font-bold text-lg shrink-0">
            {{ $val }}
        </div>
        <div class="text-sm font-semibold text-slate-800">{{ $label }}</div>
    </div>
    @endforeach
</div>

@if ($needReview > 0)
<div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-4 flex items-center gap-3">
    <svg class="w-5 h-5 text-orange-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
    </svg>
    <span class="text-sm text-orange-800 font-medium">Ada <strong>{{ $needReview }}</strong> WO yang menunggu review requester (auto-confirm 2 hari).</span>
    <a href="{{ route('work-orders.index', ['status' => 'completed']) }}" class="ml-auto text-sm text-orange-700 font-semibold hover:underline">Lihat →</a>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Recent WOs --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800">WO QA Terbaru</h3>
            <a href="{{ route('work-orders.index') }}" class="text-xs text-blue-600 hover:underline">Lihat semua</a>
        </div>
        <div class="space-y-2">
            @forelse ($recentWos as $wo)
            <a href="{{ route('work-orders.show', $wo) }}"
                class="flex items-center justify-between p-3 border border-slate-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-800 truncate">{{ $wo->title }}</div>
                    <div class="text-xs text-slate-500">{{ $wo->wo_number }} · {{ $wo->requester->name }}</div>
                </div>
                <span class="ml-3 shrink-0 text-xs px-2 py-1 rounded-full {{ \App\Models\WorkOrder::statusColor($wo->status) }}">
                    {{ \App\Models\WorkOrder::statusLabel($wo->status) }}
                </span>
            </a>
            @empty
            <p class="text-sm text-slate-400 py-4 text-center">Belum ada WO.</p>
            @endforelse
        </div>
    </div>

    {{-- QA Member Status --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800">QA Member</h3>
            <a href="{{ route('performance.qa') }}" class="text-xs text-blue-600 hover:underline">Performance →</a>
        </div>
        <div class="space-y-3">
            @forelse ($qaMembers as $member)
            @php
                $activeCount = \App\Models\WorkOrder::where('assigned_member_id', $member->id)
                    ->whereIn('status', ['assigned_member', 'rework'])->count();
            @endphp
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg">
                <div>
                    <div class="text-sm font-medium text-slate-800">{{ $member->name }}</div>
                    <div class="text-xs text-slate-500">SR: {{ $member->service_rate > 0 ? $member->service_rate . '%' : 'N/A' }}</div>
                </div>
                <span class="text-xs px-2 py-1 rounded-full {{ $activeCount > 0 ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500' }}">
                    {{ $activeCount > 0 ? $activeCount . ' WO aktif' : 'Idle' }}
                </span>
            </div>
            @empty
            <p class="text-sm text-slate-400 py-4 text-center">Belum ada QA member.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ────────── QA MEMBER ────────── --}}
@elseif ($user->isQaMember())

<div class="grid grid-cols-3 gap-4 mb-6">
    @foreach ([
        ['WO Aktif', $myActive, 'bg-blue-600'],
        ['Overdue', $overdue, 'bg-red-600'],
        ['Selesai', $myFinished, 'bg-green-600'],
    ] as [$label, $val, $color])
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 {{ $color }} rounded-xl flex items-center justify-center text-white font-bold text-lg shrink-0">
            {{ $val }}
        </div>
        <div class="text-sm font-semibold text-slate-800">{{ $label }}</div>
    </div>
    @endforeach
</div>

<div class="bg-white rounded-xl shadow-sm p-5 mb-6 flex items-center gap-6">
    <div class="text-center">
        <div class="text-4xl font-bold {{ $user->service_rate >= 80 ? 'text-green-600' : ($user->service_rate >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
            {{ $user->service_rate > 0 ? $user->service_rate . '%' : 'N/A' }}
        </div>
        <div class="text-sm text-slate-500 mt-1">Service Rate Kamu</div>
    </div>
    <div class="flex-1">
        <div class="text-sm text-slate-600 mb-1">Berdasarkan {{ $myFinished }} WO QA yang selesai</div>
        <div class="w-full bg-slate-100 rounded-full h-3">
            <div class="h-3 rounded-full {{ $user->service_rate >= 80 ? 'bg-green-500' : ($user->service_rate >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                style="width: {{ min(100, $user->service_rate) }}%"></div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-slate-800">WO QA Aktif Saya</h3>
        <a href="{{ route('work-orders.index') }}" class="text-xs text-blue-600 hover:underline">Lihat semua</a>
    </div>
    <div class="space-y-2">
        @forelse ($myWos as $wo)
        <a href="{{ route('work-orders.show', $wo) }}"
            class="flex items-center justify-between p-4 border border-slate-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition">
            <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-800 truncate">{{ $wo->title }}</div>
                <div class="text-xs text-slate-500">{{ $wo->wo_number }} · {{ $wo->requester->name }}</div>
                @if ($wo->deadline)
                <div class="text-xs mt-1 {{ $wo->isOverdue() ? 'text-red-600 font-semibold' : 'text-slate-400' }}">
                    Deadline: {{ $wo->deadline->format('d M Y') }}
                    @if ($wo->status === 'rework') · Rework deadline: {{ $wo->rework_deadline?->format('d M Y') }} @endif
                </div>
                @endif
            </div>
            <span class="ml-3 shrink-0 text-xs px-2 py-1 rounded-full {{ \App\Models\WorkOrder::statusColor($wo->status) }}">
                {{ \App\Models\WorkOrder::statusLabel($wo->status) }}
            </span>
        </a>
        @empty
        <p class="text-sm text-slate-400 py-4 text-center">Tidak ada WO QA aktif.</p>
        @endforelse
    </div>
</div>

{{-- ────────── REGULAR USER ────────── --}}
@else

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @foreach ([
        ['WO Saya', $pendingWo + $activeWo + $finishedWo, 'bg-blue-600'],
        ['Pending', $pendingWo, 'bg-yellow-500'],
        ['Aktif', $activeWo, 'bg-indigo-600'],
        ['Selesai', $finishedWo, 'bg-green-600'],
    ] as [$label, $val, $color])
    <div class="bg-white rounded-xl shadow-sm p-5 flex items-center gap-4">
        <div class="w-12 h-12 {{ $color }} rounded-xl flex items-center justify-center text-white font-bold text-lg shrink-0">
            {{ $val }}
        </div>
        <div class="text-sm font-semibold text-slate-800">{{ $label }}</div>
    </div>
    @endforeach
</div>

@if ($needReview > 0)
<div class="bg-orange-50 border border-orange-200 rounded-xl p-4 mb-4 flex items-center gap-3">
    <svg class="w-5 h-5 text-orange-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
    </svg>
    <span class="text-sm text-orange-800 font-medium">Ada <strong>{{ $needReview }}</strong> WO yang menunggu review kamu!</span>
    <a href="{{ route('work-orders.index', ['status' => 'completed']) }}" class="ml-auto text-sm text-orange-700 font-semibold hover:underline">Review Sekarang →</a>
</div>
@endif

<div class="bg-white rounded-xl shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-slate-800">Work Order Saya</h3>
        <a href="{{ route('work-orders.create') }}"
            class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Buat WO Baru
        </a>
    </div>
    <div class="space-y-2">
        @forelse ($myWos as $wo)
        <a href="{{ route('work-orders.show', $wo) }}"
            class="flex items-center justify-between p-4 border border-slate-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition">
            <div class="min-w-0">
                <div class="text-sm font-semibold text-slate-800 truncate">{{ $wo->title }}</div>
                <div class="text-xs text-slate-500">
                    {{ $wo->wo_number }} · {{ ucfirst($wo->destination) }}
                    @if ($wo->assignedMember) · {{ $wo->assignedMember->name }} @endif
                </div>
                @if ($wo->deadline)
                <div class="text-xs text-slate-400 mt-0.5">Deadline: {{ $wo->deadline->format('d M Y') }}</div>
                @endif
            </div>
            <span class="ml-3 shrink-0 text-xs px-2 py-1 rounded-full {{ \App\Models\WorkOrder::statusColor($wo->status) }}">
                {{ \App\Models\WorkOrder::statusLabel($wo->status) }}
            </span>
        </a>
        @empty
        <div class="text-center py-8">
            <p class="text-slate-400 text-sm mb-3">Belum ada Work Order.</p>
            <a href="{{ route('work-orders.create') }}"
                class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                Buat WO Pertama
            </a>
        </div>
        @endforelse
    </div>
</div>

@endif
@endsection
