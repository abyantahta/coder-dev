@extends('layouts.app')
@section('title', 'Warehouse MTC Dashboard')
@section('page-title', 'Warehouse MTC — Dashboard')

@push('head')
<script src="{{ asset('js/chart.umd.min.js') }}"></script>
@endpush

@section('content')

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    @foreach ([
        ['Menunggu PR', $stats['pending'],      'bg-amber-500',   'Perlu dibuatkan PR'],
        ['PR Aktif',    $stats['in_progress'],  'bg-blue-600',    'Sedang dipesan'],
        ['Overdue',     $stats['overdue'],      'bg-red-600',     'Lewat 30 hari'],
        ['Diterima/30d',$stats['received_30d'], 'bg-green-600',   '30 hari terakhir'],
        ['Avg Hari',    $stats['avg_procurement_days'] ? $stats['avg_procurement_days'].'d' : 'N/A', 'bg-slate-600', 'Rata-rata pengadaan'],
    ] as [$label, $val, $color, $sub])
    <div class="bg-white rounded-xl shadow-sm p-4 flex items-center gap-3">
        <div class="w-11 h-11 {{ $color }} rounded-lg flex items-center justify-center text-white font-bold text-sm shrink-0">
            {{ $val }}
        </div>
        <div>
            <div class="text-sm font-semibold text-slate-800">{{ $label }}</div>
            <div class="text-xs text-slate-500">{{ $sub }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Compliance Badge --}}
@if ($stats['compliance_rate'] !== null)
<div class="bg-white rounded-xl shadow-sm p-4 mb-6 flex items-center gap-4">
    <div class="text-3xl font-bold {{ $stats['compliance_rate'] >= 80 ? 'text-green-600' : ($stats['compliance_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
        {{ $stats['compliance_rate'] }}%
    </div>
    <div>
        <div class="font-semibold text-slate-800">On-Time Delivery Rate</div>
        <div class="text-xs text-slate-500">Persentase PR yang diterima dalam 30 hari (target leadtime)</div>
    </div>
    @if ($monthlyTrend->isNotEmpty())
    <div class="flex-1 ml-4" style="height:60px">
        <canvas id="miniTrend"></canvas>
    </div>
    @endif
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    {{-- Pending — need PR ──────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">Menunggu Pembuatan PR</h3>
            <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">{{ $pendingOrders->count() }}</span>
        </div>
        <div class="divide-y divide-slate-50">
            @forelse ($pendingOrders as $order)
            <div class="p-4">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div>
                        <a href="{{ route('work-orders.show', $order->workOrder) }}"
                            class="font-medium text-slate-800 hover:text-blue-600 text-sm">
                            {{ $order->workOrder->title }}
                        </a>
                        <div class="text-xs text-slate-500 mt-0.5">
                            {{ $order->workOrder->wo_number }} · {{ $order->workOrder->requester->name }} ({{ $order->workOrder->requester->department }})
                        </div>
                        @if ($order->request_note)
                        <div class="text-xs text-amber-700 mt-1 bg-amber-50 border border-amber-100 rounded px-2 py-1">
                            Catatan UH: {{ $order->request_note }}
                        </div>
                        @endif
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::priorityColor($order->workOrder->priority) }} shrink-0">
                        {{ ucfirst($order->workOrder->priority) }}
                    </span>
                </div>

                <a href="{{ route('warehouse.orders.show', $order) }}"
                    class="inline-block bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-1.5 rounded-lg transition">
                    Pilih Sparepart & Buat PR
                </a>
            </div>
            @empty
            <div class="p-6 text-center text-slate-400 text-sm">Tidak ada yang menunggu PR.</div>
            @endforelse
        </div>
    </div>

    {{-- Active PR — tracking ────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-semibold text-slate-800">PR Aktif (Dalam Pengiriman)</h3>
            <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ $activeOrders->count() }}</span>
        </div>
        <div class="divide-y divide-slate-50">
            @forelse ($activeOrders as $order)
            @php $overdue = $order->isOverdue(); @endphp
            <div class="p-4 {{ $overdue ? 'bg-red-50' : '' }}">
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div>
                        <a href="{{ route('work-orders.show', $order->workOrder) }}"
                            class="font-medium text-slate-800 hover:text-blue-600 text-sm">
                            {{ $order->workOrder->title }}
                        </a>
                        <div class="text-xs text-slate-500 mt-0.5">
                            {{ $order->workOrder->wo_number }} · PR: <strong>{{ $order->pr_number }}</strong>
                        </div>
                        <div class="text-xs mt-1 {{ $overdue ? 'text-red-600 font-semibold' : 'text-slate-500' }}">
                            Estimasi tiba: {{ $order->expected_arrival?->format('d M Y') }}
                            @if ($overdue) <span class="text-red-600">(OVERDUE)</span> @endif
                        </div>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full {{ \App\Models\WorkOrder::priorityColor($order->workOrder->priority) }} shrink-0">
                        {{ ucfirst($order->workOrder->priority) }}
                    </span>
                </div>

                {{-- Days remaining --}}
                @php
                    $daysLeft = $order->expected_arrival ? now()->diffInDays($order->expected_arrival, false) : null;
                @endphp
                @if ($daysLeft !== null)
                <div class="flex items-center gap-2 mb-3">
                    <div class="flex-1 bg-slate-100 rounded-full h-1.5">
                        @php $progress = max(0, min(100, 100 - ($daysLeft / 30 * 100))); @endphp
                        <div class="h-1.5 rounded-full {{ $overdue ? 'bg-red-500' : 'bg-blue-500' }}"
                            style="width: {{ $progress }}%"></div>
                    </div>
                    <span class="text-xs {{ $overdue ? 'text-red-600 font-semibold' : 'text-slate-500' }}">
                        {{ $overdue ? round(abs($daysLeft)).' hari overdue' : round($daysLeft).' hari lagi' }}
                    </span>
                </div>
                @endif

                <form method="POST" action="{{ route('warehouse.receive', $order) }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="note" placeholder="Catatan receiving (opsional)"
                        class="flex-1 border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white text-xs font-semibold px-4 py-1.5 rounded-lg transition whitespace-nowrap">
                        ✓ Terima Barang
                    </button>
                </form>
            </div>
            @empty
            <div class="p-6 text-center text-slate-400 text-sm">Tidak ada PR aktif.</div>
            @endforelse
        </div>
    </div>

</div>

{{-- Monthly Chart + Recently Received ─────────────────────────────────────── --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Trend Pengadaan Bulanan (6 Bulan)</h3>
        @if ($monthlyTrend->isNotEmpty())
        <div style="height:220px; position:relative">
            <canvas id="procurementChart"></canvas>
        </div>
        @else
        <p class="text-slate-400 text-sm text-center py-8">Belum ada data pengadaan.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-4">Barang Baru Diterima (30 Hari)</h3>
        <div class="space-y-2">
            @forelse ($receivedOrders as $order)
            <div class="flex items-center justify-between p-3 bg-green-50 border border-green-100 rounded-lg">
                <div class="min-w-0">
                    <div class="text-sm font-medium text-slate-800 truncate">{{ $order->workOrder->title }}</div>
                    <div class="text-xs text-slate-500">PR: {{ $order->pr_number }} · {{ $order->received_at?->format('d M Y') }}</div>
                </div>
                @php $days = $order->procurement_days; @endphp
                <span class="ml-3 text-xs font-semibold shrink-0 {{ $days !== null && $days <= 30 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $days !== null ? $days.'h' : '—' }}
                </span>
            </div>
            @empty
            <p class="text-slate-400 text-sm text-center py-6">Belum ada barang diterima bulan ini.</p>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
@if ($monthlyTrend->isNotEmpty())
const months = {!! $monthlyTrend->pluck('month')->map(fn($m) => \Carbon\Carbon::parse($m)->format('M Y'))->toJson() !!};
const totals  = {!! $monthlyTrend->pluck('total')->toJson() !!};
const avgDays = {!! $monthlyTrend->map(fn($r) => round($r->avg_days ?? 0, 1))->toJson() !!};

new Chart(document.getElementById('procurementChart'), {
    type: 'bar',
    data: {
        labels: months,
        datasets: [
            { label: 'Jumlah PR', data: totals, backgroundColor: '#5B6472', borderRadius: 4, yAxisID: 'y' },
            { label: 'Avg Hari', data: avgDays, type: 'line', borderColor: '#F2790B', backgroundColor: '#F2790B', tension: 0.3,
              pointBackgroundColor: '#F2790B', yAxisID: 'y2' },
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: true, position: 'top' } },
        scales: {
            y:  { beginAtZero: true, title: { display: true, text: 'Jumlah PR' } },
            y2: { beginAtZero: true, position: 'right', title: { display: true, text: 'Hari' },
                  grid: { drawOnChartArea: false } }
        }
    }
});

new Chart(document.getElementById('miniTrend'), {
    type: 'line',
    data: {
        labels: months,
        datasets: [{ data: avgDays, borderColor: '#F2790B', borderWidth: 2, pointRadius: 0, tension: 0.3 }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { display: false }, y: { display: false } }
    }
});
@endif
</script>
@endpush
