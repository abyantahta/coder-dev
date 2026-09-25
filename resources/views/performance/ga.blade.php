@extends('layouts.app')
@section('title', 'Performance GA')
@section('page-title', 'Performance GA')

@section('content')

@php
    $srClass = fn ($v) => $v === null ? 'text-slate-400' : ($v >= 80 ? 'text-green-600' : ($v >= 60 ? 'text-yellow-600' : 'text-red-600'));
@endphp

{{-- ── Summary Cards ── --}}
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-xs text-slate-500 mb-1">WO Selesai</div>
        <div class="text-3xl font-bold text-green-600">{{ $summary['finished'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-xs text-slate-500 mb-1">WO Aktif</div>
        <div class="text-3xl font-bold text-blue-600">{{ $summary['active'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-xs text-slate-500 mb-1">Avg SR (6 bln)</div>
        <div class="text-3xl font-bold {{ $srClass($summary['avg_score']) }}">
            {{ $summary['avg_score'] !== null ? $summary['avg_score'].'%' : '—' }}
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-xs text-slate-500 mb-1">Tepat Waktu (6 bln)</div>
        <div class="text-3xl font-bold {{ $srClass($summary['on_time_rate']) }}">
            {{ $summary['on_time_rate'] !== null ? $summary['on_time_rate'].'%' : '—' }}
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-xs text-slate-500 mb-1">Menunggu Material</div>
        <div class="text-3xl font-bold text-amber-500">{{ $summary['waiting_material'] }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-xs text-slate-500 mb-1">Overdue</div>
        <div class="text-3xl font-bold {{ $summary['overdue'] > 0 ? 'text-red-600' : 'text-slate-400' }}">{{ $summary['overdue'] }}</div>
        @if ($summary['need_review'] > 0)
        <div class="text-xs text-orange-500 mt-0.5">{{ $summary['need_review'] }} menunggu review requester</div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Tren SR GA (6 Bulan)</h3>
        @if ($monthlyTrend->isEmpty())
        <p class="text-sm text-slate-400 py-10 text-center">Belum ada WO GA yang selesai dalam 6 bulan terakhir.</p>
        @else
        <canvas id="srTrendChart" height="200"></canvas>
        @endif
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Volume WO per Status</h3>
        @if ($woByStatus->isEmpty())
        <p class="text-sm text-slate-400 py-10 text-center">Belum ada WO GA.</p>
        @else
        <canvas id="woStatusChart" height="200"></canvas>
        @endif
    </div>
</div>

{{-- ── Staff Performance Table ── --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">Performa Staff GA</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Nama</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">WO Aktif</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">WO Selesai</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Total Rework</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Tepat Waktu</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">SR (%)</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($staff as $row)
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800">{{ $row->user->name }}</div>
                        <div class="text-xs text-slate-400">{{ $row->user->deptRole?->name }}</div>
                    </td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $row->active }}</td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $row->finished }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="{{ $row->rework > 0 ? 'text-pink-600 font-semibold' : 'text-slate-400' }}">{{ $row->rework }}x</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="{{ $srClass($row->on_time) }}">{{ $row->on_time !== null ? $row->on_time.'%' : '—' }}</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if ($row->sr !== null)
                        <div class="inline-flex items-center gap-2">
                            <span class="font-bold text-base {{ $srClass($row->sr) }}">{{ $row->sr }}%</span>
                            <div class="w-16 bg-slate-100 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full {{ $row->sr >= 80 ? 'bg-green-500' : ($row->sr >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                    style="width: {{ $row->sr }}%"></div>
                            </div>
                        </div>
                        @else
                        <span class="text-slate-300 text-xs">Belum ada data</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if ($row->current)
                        <a href="{{ route('work-orders.show', $row->current) }}"
                            class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::statusColor($row->current->status) }}">
                            {{ \App\Models\WorkOrder::statusLabel($row->current->status) }}
                        </a>
                        @else
                        <span class="text-xs text-slate-400">Idle</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-slate-400 py-8">Belum ada staff GA terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- ── Per Category ── --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-800">WO per Kategori</h3>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Kategori</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Total</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Aktif</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Selesai</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Avg Skor</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($categories as $cat)
                <tr>
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800">{{ $cat->name }}</div>
                        <div class="text-xs text-slate-400">Leadtime {{ $cat->leadtime_days }} hari kerja</div>
                    </td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $cat->total }}</td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $cat->active }}</td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $cat->finished }}</td>
                    <td class="px-4 py-3 text-center font-semibold {{ $srClass($cat->avg_score) }}">{{ $cat->avg_score ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-slate-400 py-8">Belum ada kategori GA.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Material Procurement ── --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-slate-800">Pengadaan Material</h3>
            <a href="{{ route('warehouse.index') }}" class="text-xs text-blue-600 hover:underline">Buka Warehouse →</a>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="rounded-lg bg-slate-50 p-3">
                <div class="text-xs text-slate-500">Menunggu PR</div>
                <div class="text-xl font-bold text-amber-500">{{ $procurement['waiting_pr'] }}</div>
            </div>
            <div class="rounded-lg bg-slate-50 p-3">
                <div class="text-xs text-slate-500">PR Dibuat (belum diterima)</div>
                <div class="text-xl font-bold text-blue-600">{{ $procurement['pr_created'] }}</div>
            </div>
            <div class="rounded-lg bg-slate-50 p-3">
                <div class="text-xs text-slate-500">Diterima (6 bln)</div>
                <div class="text-xl font-bold text-green-600">{{ $procurement['received_6m'] }}</div>
            </div>
            <div class="rounded-lg bg-slate-50 p-3">
                <div class="text-xs text-slate-500">Rata-rata PR → Diterima</div>
                <div class="text-xl font-bold text-slate-700">{{ $procurement['avg_days'] !== null ? $procurement['avg_days'].' hari' : '—' }}</div>
            </div>
        </div>
        <div class="space-y-2">
            @forelse ($openOrders as $order)
            <a href="{{ route('warehouse.orders.show', $order) }}"
                class="flex items-center justify-between p-3 border border-slate-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-800 truncate">{{ $order->displayTitle() }}</div>
                    <div class="text-xs text-slate-500">{{ $order->displayReference() }}{{ $order->pr_number ? ' · '.$order->pr_number : '' }}</div>
                </div>
                <span class="ml-3 shrink-0 text-xs px-2 py-1 rounded-full {{ \App\Models\WoPartOrder::statusColor($order->status) }}">
                    {{ \App\Models\WoPartOrder::statusLabel($order->status) }}
                </span>
            </a>
            @empty
            <p class="text-sm text-slate-400 py-4 text-center">Tidak ada pengadaan material yang sedang berjalan.</p>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
<script>
const trendData = @json($monthlyTrend);
const statusData = @json($woByStatus);

if (document.getElementById('srTrendChart')) {
    new Chart(document.getElementById('srTrendChart'), {
        type: 'bar',
        data: {
            labels: trendData.map(d => d.month),
            datasets: [
                {
                    label: 'Avg SR (%)',
                    data: trendData.map(d => d.avg_score),
                    backgroundColor: 'rgba(242,121,11,0.18)',
                    borderColor: 'rgb(242,121,11)',
                    borderWidth: 2,
                    borderRadius: 4,
                    yAxisID: 'y',
                },
                {
                    label: 'Jumlah WO',
                    data: trendData.map(d => d.total),
                    type: 'line',
                    borderColor: 'rgb(46,115,80)',
                    backgroundColor: 'rgba(46,115,80,0.12)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointRadius: 4,
                    yAxisID: 'y2',
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y:  { beginAtZero: true, max: 100, title: { display: true, text: 'SR (%)' } },
                y2: { beginAtZero: true, position: 'right', title: { display: true, text: 'Jumlah WO' }, grid: { drawOnChartArea: false } },
            }
        }
    });
}

const statusLabels = {
    pending: 'Pending', accepted: 'Diterima', assigned_member: 'Dalam Pengerjaan',
    pending_parts: 'Menunggu Material', parts_ordered: 'Material Dipesan', parts_received: 'Material Diterima',
    completed: 'Menunggu Review', rework: 'Rework', finished: 'Selesai',
    rejected: 'Ditolak', cancelled: 'Dibatalkan',
};
const statusColors = {
    pending: '#EAD49B', accepted: '#C3CED8', assigned_member: '#B9BEC7',
    pending_parts: '#F6D38B', parts_ordered: '#F6B172', parts_received: '#C9DDB0',
    completed: '#F9CFA4', rework: '#EDC0B5', finished: '#B6D4C1',
    rejected: '#F0BCB6', cancelled: '#E0DACB',
};
if (document.getElementById('woStatusChart')) {
    const entries = Object.entries(statusData).filter(([, v]) => v > 0);
    new Chart(document.getElementById('woStatusChart'), {
        type: 'doughnut',
        data: {
            labels: entries.map(([k]) => statusLabels[k] || k),
            datasets: [{
                data: entries.map(([, v]) => v),
                backgroundColor: entries.map(([k]) => statusColors[k] || '#E0DACB'),
                borderWidth: 1,
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'right' } } }
    });
}
</script>
@endpush
