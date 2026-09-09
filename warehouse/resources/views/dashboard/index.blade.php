@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')

@php $isPlainUser = auth()->user()->role === 'user'; @endphp

{{-- Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="stat-value text-primary">{{ $stats['pending_approvals'] }}</div>
                    <div class="stat-label">Menunggu Approval</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <div>
                    <div class="stat-value text-success">{{ $stats['today_transactions'] }}</div>
                    <div class="stat-label">Transaksi Hari Ini</div>
                </div>
            </div>
        </div>
    </div>
    @if($isPlainUser)
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <div class="stat-value text-primary" style="font-size:18px;">Rp {{ number_format($currentMonthBudget, 0, ',', '.') }}</div>
                    <div class="stat-label">Budget Bulan Ini</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon {{ $currentMonthConsumed > $currentMonthBudget ? 'bg-danger' : 'bg-success' }} bg-opacity-10 {{ $currentMonthConsumed > $currentMonthBudget ? 'text-danger' : 'text-success' }}">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div>
                    <div class="stat-value {{ $currentMonthConsumed > $currentMonthBudget ? 'text-danger' : 'text-success' }}" style="font-size:18px;">Rp {{ number_format($currentMonthConsumed, 0, ',', '.') }}</div>
                    <div class="stat-label">Terpakai Bulan Ini</div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <div class="stat-value text-info">{{ $stats['total_items'] }}</div>
                    <div class="stat-label">Total Item</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <a href="{{ route('admin.min-stock.index') }}" class="card stat-card text-decoration-none">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="stat-value text-warning">{{ $stats['low_stock_items'] }}</div>
                    <div class="stat-label">Stok Menipis</div>
                </div>
            </div>
        </a>
    </div>
    @endif
</div>

{{-- Budget vs Actual (departemen user) --}}
@if(!empty($budgetHistory))
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-graph-up me-2 text-primary"></i>Budget vs Actual — 12 Bulan Terakhir</div>
    <div class="card-body">
        <div style="position:relative;height:280px;">
            <canvas id="budgetChart"></canvas>
        </div>
    </div>
</div>
@endif

{{-- ===== PRICE SECTION (SUPERADMIN ONLY) ===== --}}
@if(auth()->user()->isSuperAdmin() && $priceStats)
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-danger px-2 py-1" style="font-size:11px;">SUPERADMIN</span>
            <span class="fw-semibold" style="font-size:15px;">Ringkasan Nilai Inventory & Transaksi</span>
            @if($priceStats['items_no_price'] > 0)
            <a href="{{ route('master.items.index') }}" class="ms-auto badge bg-warning text-dark text-decoration-none" style="font-size:11px;">
                <i class="bi bi-exclamation-triangle me-1"></i>{{ $priceStats['items_no_price'] }} item belum ada harga
            </a>
            @endif
        </div>
    </div>

    {{-- Nilai Inventory --}}
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="border-left:4px solid #4680ff;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-database-fill-gear"></i>
                </div>
                <div>
                    <div class="stat-label">Nilai Inventory</div>
                    <div class="stat-value text-primary" style="font-size:20px;">
                        Rp {{ number_format($priceStats['total_inventory_value'], 0, ',', '.') }}
                    </div>
                    <div class="stat-change text-muted">{{ $priceStats['items_with_price'] }} item ada harga</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Nilai Keluar Bulan Ini --}}
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="border-left:4px solid #dc3545;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-box-arrow-up-right"></i>
                </div>
                <div>
                    <div class="stat-label">Keluar Bulan Ini</div>
                    <div class="stat-value text-danger" style="font-size:20px;">
                        Rp {{ number_format($priceStats['goods_out_value'], 0, ',', '.') }}
                    </div>
                    <div class="stat-change text-muted">{{ now()->format('F Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Nilai Masuk Bulan Ini --}}
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="border-left:4px solid #198754;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-box-arrow-in-down-right"></i>
                </div>
                <div>
                    <div class="stat-label">Masuk Bulan Ini</div>
                    <div class="stat-value text-success" style="font-size:20px;">
                        Rp {{ number_format($priceStats['goods_in_value'], 0, ',', '.') }}
                    </div>
                    <div class="stat-change text-muted">{{ now()->format('F Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Nilai Pengembalian --}}
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="border-left:4px solid #0dcaf0;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-arrow-return-left"></i>
                </div>
                <div>
                    <div class="stat-label">Dikembalikan</div>
                    <div class="stat-value text-info" style="font-size:20px;">
                        Rp {{ number_format($priceStats['return_value'], 0, ',', '.') }}
                    </div>
                    <div class="stat-change text-muted">{{ now()->format('F Y') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Chart Nilai Transaksi --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up me-2 text-primary"></i>Nilai Transaksi {{ now()->year }} (Rp)</span>
            </div>
            <div class="card-body">
                <canvas id="valueChart" height="90"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Items by Value --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-trophy me-2 text-warning"></i>Top 5 Nilai Inventory
            </div>
            <div class="card-body p-0">
                @forelse($priceStats['top_items'] as $i => $topItem)
                <div class="d-flex align-items-center gap-3 px-3 py-2 {{ $loop->last ? '' : 'border-bottom' }}">
                    <div class="fw-bold text-muted" style="font-size:18px;width:24px;">{{ $i + 1 }}</div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold text-truncate" style="font-size:13px;" title="{{ $topItem['description'] }}">
                            {{ $topItem['description'] }}
                        </div>
                        <div class="text-muted" style="font-size:11px;">
                            {{ number_format($topItem['qty'], 0) }} {{ $topItem['unit'] }}
                            × Rp {{ number_format($topItem['price'], 0, ',', '.') }}
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary" style="font-size:13px;">
                            Rp {{ number_format($topItem['value'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-4" style="font-size:13px;">
                    Belum ada item dengan harga
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endif

<div class="row g-3 mb-4">
    {{-- Chart --}}
    <div class="col-lg-12">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart me-2 text-primary"></i>Grafik Transaksi {{ now()->year }}</span>
            </div>
            <div class="card-body">
                <canvas id="transactionChart" height="90"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Recent Transactions --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>Transaksi Terbaru</span>
        <a href="{{ route('transactions.goods-out.index') }}" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Tipe</th>
                        <th>User</th>
                        <th>Departemen</th>
                        <th>Item</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentTransactions as $trx)
                    <tr>
                        <td><span class="fw-semibold text-primary">{{ $trx->trans_no }}</span></td>
                        <td>
                            @php
                                $typeIcon = match($trx->trans_type) {
                                    'goods_out'    => 'bi-box-arrow-up-right text-danger',
                                    'goods_in'     => 'bi-box-arrow-in-down-right text-success',
                                    'memo_in'      => 'bi-journal-plus text-info',
                                    'memo_out'     => 'bi-journal-minus text-warning',
                                    'goods_return' => 'bi-arrow-return-left text-primary',
                                    default        => 'bi-arrow-left-right'
                                };
                            @endphp
                            <i class="bi {{ $typeIcon }} me-1"></i>{{ $trx->getTypeLabel() }}
                        </td>
                        <td>{{ $trx->user->name }}</td>
                        <td>{{ $trx->department?->name ?? '-' }}</td>
                        <td><span class="badge bg-secondary">{{ $trx->details->count() }} item</span></td>
                        <td>{{ $trx->trans_date->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $trx->getStatusBadge() }}">{{ $trx->getStatusLabel() }}</span>
                        </td>
                        <td>
                            <a href="{{ route('admin.approvals.show', $trx->id) }}" class="btn btn-sm btn-light">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// Chart transaksi (jumlah)
const ctx = document.getElementById('transactionChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($chartLabels) !!},
        datasets: [
            {
                label: 'Keluar',
                data: {!! json_encode($chartOut->values()) !!},
                backgroundColor: 'rgba(220,53,69,.7)',
                borderRadius: 4,
            },
            {
                label: 'Masuk',
                data: {!! json_encode($chartIn->values()) !!},
                backgroundColor: 'rgba(70,128,255,.7)',
                borderRadius: 4,
            },
            {
                label: 'Kembali',
                data: {!! json_encode($chartReturn->values()) !!},
                backgroundColor: 'rgba(13,202,240,.7)',
                borderRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f0f2f5' } },
            x: { grid: { display: false } }
        }
    }
});

// Chart nilai (superadmin)
@if(auth()->user()->isSuperAdmin() && $priceStats)
const vCtx = document.getElementById('valueChart').getContext('2d');
new Chart(vCtx, {
    type: 'line',
    data: {
        labels: {!! json_encode($chartLabels) !!},
        datasets: [
            {
                label: 'Nilai Keluar (Rp)',
                data: {!! json_encode($priceStats['value_chart_out']) !!},
                borderColor: 'rgba(220,53,69,1)',
                backgroundColor: 'rgba(220,53,69,.08)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
            },
            {
                label: 'Nilai Masuk (Rp)',
                data: {!! json_encode($priceStats['value_chart_in']) !!},
                borderColor: 'rgba(70,128,255,1)',
                backgroundColor: 'rgba(70,128,255,.08)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label + ': Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: '#f0f2f5' },
                ticks: { callback: v => 'Rp ' + (v >= 1e6 ? (v/1e6).toFixed(1)+'jt' : v.toLocaleString('id-ID')) }
            },
            x: { grid: { display: false } }
        }
    }
});
@endif

@if(!empty($budgetHistory))
const budgetChartEl = document.getElementById('budgetChart');
if (budgetChartEl) {
    const rupiah = (v) => 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    new Chart(budgetChartEl, {
        type: 'bar',
        data: {
            labels: @json(collect($budgetHistory)->pluck('label')),
            datasets: [
                {
                    type: 'line',
                    label: 'Budget',
                    data: @json(collect($budgetHistory)->pluck('budget')),
                    borderColor: '#2a78d6',
                    backgroundColor: '#2a78d6',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#2a78d6',
                    tension: 0,
                    fill: false,
                    order: 0,
                },
                {
                    type: 'bar',
                    label: 'Actual',
                    data: @json(collect($budgetHistory)->pluck('consumed')),
                    backgroundColor: '#eb6834',
                    borderRadius: 4,
                    maxBarThickness: 28,
                    order: 1,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, boxHeight: 12, font: { size: 12 } } },
                tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label}: ${rupiah(ctx.parsed.y)}` } },
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#898781', font: { size: 11 } } },
                y: { grid: { color: '#e1e0d9' }, ticks: { color: '#898781', font: { size: 11 }, callback: (v) => rupiah(v) } },
            },
        },
    });
}
@endif
</script>
@endpush
