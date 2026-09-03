@extends('layouts.app')
@section('title', 'Performance QA')
@section('page-title', 'Performance QA')

@section('content')

{{-- ── Summary Cards ── --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
        $totalFinished = $woByStatus->get('finished', 0);
        $totalActive   = collect($woByStatus)->except(['finished','cancelled','rejected'])->sum();
        $avgScore      = $monthlyTrend->isNotEmpty() ? round($monthlyTrend->avg('avg_score'), 1) : null;
        $needReview    = $woByStatus->get('completed', 0);
    @endphp
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-xs text-slate-500 mb-1">WO Selesai</div>
        <div class="text-3xl font-bold text-green-600">{{ $totalFinished }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-xs text-slate-500 mb-1">WO Aktif</div>
        <div class="text-3xl font-bold text-blue-600">{{ $totalActive }}</div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-xs text-slate-500 mb-1">Avg SR (6 bln)</div>
        <div class="text-3xl font-bold {{ $avgScore >= 80 ? 'text-green-600' : ($avgScore >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
            {{ $avgScore !== null ? $avgScore . '%' : '—' }}
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="text-xs text-slate-500 mb-1">Perlu Review</div>
        <div class="text-3xl font-bold text-orange-500">{{ $needReview }}</div>
        @if ($needReview > 0)
        <div class="text-xs text-orange-400 mt-0.5">auto-confirm dalam 2 hari</div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Monthly SR Trend Chart --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Tren SR QA (6 Bulan)</h3>
        <canvas id="srTrendChart" height="200"></canvas>
    </div>

    {{-- WO Volume by Status --}}
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Volume WO per Status</h3>
        <canvas id="woStatusChart" height="200"></canvas>
    </div>

</div>

{{-- ── QA Member Performance Table ── --}}
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-800">Performa QA Member</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Nama</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">WO Aktif</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">WO Selesai</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Total Rework</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">SR (%)</th>
                    <th class="text-center text-xs font-semibold text-slate-500 uppercase tracking-wider px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($members as $member)
                @php
                    $mActive   = \App\Models\WorkOrder::where('assigned_member_id', $member->id)->active()->count();
                    $mFinished = \App\Models\WorkOrder::where('assigned_member_id', $member->id)->where('status', 'finished')->where('destination', 'qa')->count();
                    $mRework   = \App\Models\WorkOrder::where('assigned_member_id', $member->id)->where('destination', 'qa')->sum('rework_count');
                    $sr        = $member->service_rate;
                @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-4 py-3">
                        <div class="font-medium text-slate-800">{{ $member->name }}</div>
                        <div class="text-xs text-slate-400">{{ $member->department }}</div>
                    </td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $mActive }}</td>
                    <td class="px-4 py-3 text-center text-slate-600">{{ $mFinished }}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="{{ $mRework > 0 ? 'text-pink-600 font-semibold' : 'text-slate-400' }}">{{ $mRework }}x</span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if ($sr > 0)
                        <div class="inline-flex items-center gap-2">
                            <span class="font-bold text-base {{ $sr >= 80 ? 'text-green-600' : ($sr >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $sr }}%
                            </span>
                            <div class="w-16 bg-slate-100 rounded-full h-1.5">
                                <div class="h-1.5 rounded-full {{ $sr >= 80 ? 'bg-green-500' : ($sr >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                                    style="width: {{ $sr }}%"></div>
                            </div>
                        </div>
                        @else
                        <span class="text-slate-300 text-xs">Belum ada data</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php $currentWo = \App\Models\WorkOrder::where('assigned_member_id', $member->id)->whereIn('status', ['assigned_member', 'rework'])->first(); @endphp
                        @if ($currentWo)
                        <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::statusColor($currentWo->status) }}">
                            {{ \App\Models\WorkOrder::statusLabel($currentWo->status) }}
                        </span>
                        @else
                        <span class="text-xs text-slate-400">Idle</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-slate-400 py-8">Belum ada QA member terdaftar.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const trendData = @json($monthlyTrend);
const statusData = @json($woByStatus);

// SR Trend Chart
new Chart(document.getElementById('srTrendChart'), {
    type: 'bar',
    data: {
        labels: trendData.map(d => d.month),
        datasets: [
            {
                label: 'Avg SR (%)',
                data: trendData.map(d => d.avg_score),
                backgroundColor: 'rgba(99,102,241,0.2)',
                borderColor: 'rgb(99,102,241)',
                borderWidth: 2,
                borderRadius: 4,
                yAxisID: 'y',
            },
            {
                label: 'Jumlah WO',
                data: trendData.map(d => d.total),
                type: 'line',
                borderColor: 'rgb(16,185,129)',
                backgroundColor: 'rgba(16,185,129,0.1)',
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

// WO Status Donut Chart
const statusLabels = {
    pending: 'Pending', accepted: 'Diterima', assigned_member: 'Dalam Pengerjaan',
    completed: 'Menunggu Review', rework: 'Rework', finished: 'Selesai',
    rejected: 'Ditolak', cancelled: 'Dibatalkan',
    forwarded_maintenance: 'Diteruskan ke MTC', forwarded_ga: 'Diteruskan ke GA',
};
const statusColors = {
    pending: '#fef08a', accepted: '#bfdbfe', assigned_member: '#e9d5ff',
    completed: '#fed7aa', rework: '#fbcfe8', finished: '#bbf7d0',
    rejected: '#fecaca', cancelled: '#e2e8f0',
    forwarded_maintenance: '#fed7aa', forwarded_ga: '#99f6e4',
};
const entries = Object.entries(statusData).filter(([, v]) => v > 0);
new Chart(document.getElementById('woStatusChart'), {
    type: 'doughnut',
    data: {
        labels: entries.map(([k]) => statusLabels[k] || k),
        datasets: [{
            data: entries.map(([, v]) => v),
            backgroundColor: entries.map(([k]) => statusColors[k] || '#e2e8f0'),
            borderWidth: 1,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'right' } }
    }
});
</script>
@endpush
