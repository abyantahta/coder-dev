@extends('layouts.app')
@section('title', 'Performance & Service Rate')
@section('page-title', 'Performance — Service Rate')

@push('head')
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
@endpush

@section('content')
@php $user = auth()->user(); @endphp

{{-- ── SR Summary Cards ── --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

    {{-- Unit SRs --}}
    @foreach ($units as $unit)
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-xs text-slate-400 mb-1 font-semibold uppercase tracking-wider">{{ $unit->name }}</div>
        <div class="flex items-end gap-3">
            <div class="text-4xl font-bold {{ $unit->service_rate >= 80 ? 'text-green-600' : ($unit->service_rate >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                {{ $unit->service_rate > 0 ? $unit->service_rate . '%' : 'N/A' }}
            </div>
            <div class="text-sm text-slate-500 mb-1">Service Rate Unit</div>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-2 mt-2">
            <div class="h-2 rounded-full {{ $unit->service_rate >= 80 ? 'bg-green-500' : ($unit->service_rate >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                style="width: {{ min(100, $unit->service_rate) }}%"></div>
        </div>
        <div class="text-xs text-slate-400 mt-1">{{ $unit->groups->count() }} group · {{ $unit->members()->count() }} member</div>
    </div>
    @endforeach

</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Member SR Bar Chart --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Service Rate per Member</h3>
        @if ($members->isNotEmpty())
        <div style="position:relative; height:{{ min(400, $members->count() * 40 + 60) }}px">
            <canvas id="memberSrChart"></canvas>
        </div>
        @else
        <p class="text-slate-400 text-sm text-center py-8">Belum ada data member.</p>
        @endif
    </div>

    {{-- Monthly Trend --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Trend SR Bulanan (6 Bulan Terakhir)</h3>
        @if ($monthlyTrend->isNotEmpty())
        <div style="position:relative; height:260px">
            <canvas id="monthlyTrendChart"></canvas>
        </div>
        @else
        <p class="text-slate-400 text-sm text-center py-8">Belum ada data WO selesai.</p>
        @endif
    </div>

</div>

{{-- WO Status Distribution --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Distribusi Status WO</h3>
        @if ($woByStatus->isNotEmpty())
        <div class="flex items-center gap-6">
            <div style="width:180px; height:180px; position:relative">
                <canvas id="woStatusChart"></canvas>
            </div>
            <div class="space-y-2">
                @foreach ($woByStatus as $status => $count)
                <div class="flex items-center gap-2 text-sm">
                    <div class="w-3 h-3 rounded-full" style="background: {{ [
                        'pending' => '#B08420',
                        'accepted' => '#5B6472',
                        'rejected' => '#B3261E',
                        'assigned_group' => '#7A8494',
                        'assigned_member' => '#12161C',
                        'completed' => '#F2790B',
                        'rework' => '#C0392B',
                        'finished' => '#2E7350',
                        'pending_parts' => '#DBBA6A',
                        'parts_ordered' => '#9AA2AE',
                        'parts_received' => '#5C9E77',
                        'forwarded_ga' => '#4A5A6B',
                        'forwarded_qa' => '#7A8494',
                        'forwarded_maintenance' => '#F6B172',
                        'cancelled' => '#C7C1B2',
                    ][$status] ?? '#9AA2AE' }}"></div>
                    <span class="text-slate-600">{{ \App\Models\WorkOrder::statusLabel($status) }}</span>
                    <span class="font-semibold text-slate-800">{{ $count }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <p class="text-slate-400 text-sm text-center py-8">Belum ada data WO.</p>
        @endif
    </div>

    {{-- Group SR Comparison --}}
    @if ($user->isSectionHead() || $user->isUnitHead())
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Perbandingan SR per Group</h3>
        @php
            $allGroups = collect();
            foreach ($units as $unit) {
                foreach ($unit->groups as $group) {
                    $allGroups->push($group);
                }
            }
        @endphp
        @if ($allGroups->isNotEmpty())
        <div style="position:relative; height:{{ min(300, $allGroups->count() * 50 + 60) }}px">
            <canvas id="groupSrChart"></canvas>
        </div>
        @else
        <p class="text-slate-400 text-sm text-center py-8">Belum ada data group.</p>
        @endif
    </div>
    @endif
</div>

{{-- Procurement KPIs (Section Head & Unit Head only) --}}
@if ($user->isSectionHead() || $user->isUnitHead())
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Trend Pengadaan Bulanan (6 Bulan)</h3>
        @if ($procurementTrend->isNotEmpty())
        <div style="height:220px; position:relative">
            <canvas id="procurementTrendChart"></canvas>
        </div>
        @else
        <p class="text-slate-400 text-sm text-center py-8">Belum ada data pengadaan.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5 flex flex-col items-center justify-center gap-3">
        <div class="text-xs text-slate-500 uppercase tracking-wider font-semibold">On-Time Delivery Rate</div>
        @if ($procurementCompliance !== null)
        <div class="text-5xl font-bold {{ $procurementCompliance >= 80 ? 'text-green-600' : ($procurementCompliance >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
            {{ $procurementCompliance }}%
        </div>
        <div class="text-sm text-slate-500 text-center">PR diterima dalam 30 hari (target leadtime)</div>
        <div class="w-48 bg-slate-100 rounded-full h-3 mt-1">
            <div class="h-3 rounded-full {{ $procurementCompliance >= 80 ? 'bg-green-500' : ($procurementCompliance >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                style="width: {{ $procurementCompliance }}%"></div>
        </div>
        @else
        <div class="text-slate-400 text-sm">Belum ada data pengadaan.</div>
        @endif
    </div>

</div>
@endif

{{-- Member Detail Table --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">Detail Member Performance</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Nama</th>
                    @if ($user->isSectionHead() || $user->isUnitHead())
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Group</th>
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Unit</th>
                    @endif
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Total WO</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Selesai</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Service Rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($members as $member)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-gradient-to-br from-violet-200 to-fuchsia-200 rounded-full flex items-center justify-center text-violet-700 text-xs font-bold shrink-0">
                                {{ strtoupper(substr($member->name, 0, 2)) }}
                            </div>
                            <div class="font-medium text-slate-800">{{ $member->name }}</div>
                        </div>
                    </td>
                    @if ($user->isSectionHead() || $user->isUnitHead())
                    <td class="px-4 py-3 text-slate-600 text-xs">{{ $member->group?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600 text-xs">{{ $member->unit?->name ?? '—' }}</td>
                    @endif
                    <td class="px-4 py-3 text-center text-slate-700">{{ $member->wo_count }}</td>
                    <td class="px-4 py-3 text-center text-slate-700">{{ $member->finished_wo_count }}</td>
                    <td class="px-4 py-3 text-center">
                        @if ($member->service_rate > 0)
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-16 bg-slate-100 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $member->service_rate >= 80 ? 'bg-green-500' : ($member->service_rate >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                    style="width: {{ min(100, $member->service_rate) }}%"></div>
                            </div>
                            <span class="font-bold {{ $member->service_rate >= 80 ? 'text-green-600' : ($member->service_rate >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $member->service_rate }}%
                            </span>
                        </div>
                        @else
                        <span class="text-slate-400 text-xs">N/A</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-slate-400 py-8">Belum ada data member.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
};

// ── Member SR Bar Chart ──
@if ($members->isNotEmpty())
new Chart(document.getElementById('memberSrChart'), {
    type: 'bar',
    data: {
        labels: {!! $members->pluck('name')->toJson() !!},
        datasets: [{
            data: {!! $members->map(fn($m) => $m->service_rate)->toJson() !!},
            backgroundColor: {!! $members->map(fn($m) => $m->service_rate >= 80 ? '#2E7350' : ($m->service_rate >= 60 ? '#B08420' : '#B3261E'))->toJson() !!},
            borderRadius: 6,
        }]
    },
    options: {
        ...chartDefaults,
        indexAxis: 'y',
        scales: {
            x: { min: 0, max: 100, ticks: { callback: v => v + '%' } },
            y: { ticks: { font: { size: 12 } } }
        },
        plugins: { ...chartDefaults.plugins, tooltip: { callbacks: { label: ctx => ctx.raw + '%' } } }
    }
});
@endif

// ── Monthly Trend ──
@if ($monthlyTrend->isNotEmpty())
new Chart(document.getElementById('monthlyTrendChart'), {
    type: 'line',
    data: {
        labels: {!! $monthlyTrend->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))->toJson() !!},
        datasets: [{
            label: 'Avg SR',
            data: {!! $monthlyTrend->pluck('avg_score')->toJson() !!},
            borderColor: '#F2790B',
            backgroundColor: 'rgba(242,121,11,0.12)',
            tension: 0.3,
            fill: true,
            pointBackgroundColor: '#F2790B',
        }]
    },
    options: {
        ...chartDefaults,
        scales: {
            y: { min: 0, max: 100, ticks: { callback: v => v + '%' } }
        },
        plugins: {
            ...chartDefaults.plugins,
            legend: { display: true },
            tooltip: { callbacks: { label: ctx => 'SR: ' + ctx.raw + '%' } }
        }
    }
});
@endif

// ── WO Status Donut ──
@if ($woByStatus->isNotEmpty())
new Chart(document.getElementById('woStatusChart'), {
    type: 'doughnut',
    data: {
        labels: {!! $woByStatus->keys()->map(fn($s) => \App\Models\WorkOrder::statusLabel($s))->toJson() !!},
        datasets: [{
            data: {!! $woByStatus->values()->toJson() !!},
            backgroundColor: {!! $woByStatus->keys()->map(fn($s) => match($s) {
                'pending' => '#B08420',
                'accepted' => '#5B6472',
                'rejected' => '#B3261E',
                'assigned_group' => '#7A8494',
                'assigned_member' => '#12161C',
                'completed' => '#F2790B',
                'rework' => '#C0392B',
                'finished' => '#2E7350',
                'pending_parts' => '#DBBA6A',
                'parts_ordered' => '#9AA2AE',
                'parts_received' => '#5C9E77',
                'forwarded_ga' => '#4A5A6B',
                'forwarded_qa' => '#7A8494',
                'forwarded_maintenance' => '#F6B172',
                default => '#9AA2AE',
            })->toJson() !!},
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
        },
        cutout: '65%',
    }
});
@endif

// ── Procurement Trend Chart ──
@if (($user->isSectionHead() || $user->isUnitHead()) && $procurementTrend->isNotEmpty())
new Chart(document.getElementById('procurementTrendChart'), {
    type: 'bar',
    data: {
        labels: {!! $procurementTrend->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))->toJson() !!},
        datasets: [
            {
                label: 'Jumlah PR',
                data: {!! $procurementTrend->pluck('total')->toJson() !!},
                backgroundColor: '#5B6472',
                borderRadius: 4,
                yAxisID: 'y',
            },
            {
                label: 'Avg Hari',
                data: {!! $procurementTrend->map(fn($r) => round($r->avg_days ?? 0, 1))->toJson() !!},
                type: 'line',
                borderColor: '#F2790B',
                tension: 0.3,
                pointBackgroundColor: '#F2790B',
                yAxisID: 'y2',
            },
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'top' } },
        scales: {
            y:  { beginAtZero: true, title: { display: true, text: 'Jumlah PR' } },
            y2: { beginAtZero: true, position: 'right', title: { display: true, text: 'Hari' }, grid: { drawOnChartArea: false } },
        }
    }
});
@endif

// ── Group SR Chart ──
@if (($user->isSectionHead() || $user->isUnitHead()) && isset($allGroups) && $allGroups->isNotEmpty())
new Chart(document.getElementById('groupSrChart'), {
    type: 'bar',
    data: {
        labels: {!! $allGroups->pluck('name')->toJson() !!},
        datasets: [{
            data: {!! $allGroups->map(fn($g) => $g->service_rate)->toJson() !!},
            backgroundColor: {!! $allGroups->map(fn($g) => $g->service_rate >= 80 ? '#2E7350' : ($g->service_rate >= 60 ? '#B08420' : '#B3261E'))->toJson() !!},
            borderRadius: 6,
        }]
    },
    options: {
        ...chartDefaults,
        indexAxis: 'y',
        scales: {
            x: { min: 0, max: 100, ticks: { callback: v => v + '%' } },
        },
        plugins: { ...chartDefaults.plugins, tooltip: { callbacks: { label: ctx => ctx.raw + '%' } } }
    }
});
@endif
</script>
@endpush
