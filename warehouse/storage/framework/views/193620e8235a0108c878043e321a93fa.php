<?php $__env->startSection('title', 'Dashboard'); ?>
<?php $__env->startSection('page-title', 'Dashboard'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item active">Dashboard</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $isPlainUser = auth()->user()->role === 'user'; ?>


<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <div class="stat-value text-primary"><?php echo e($stats['pending_approvals']); ?></div>
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
                    <div class="stat-value text-success"><?php echo e($stats['today_transactions']); ?></div>
                    <div class="stat-label">Transaksi Hari Ini</div>
                </div>
            </div>
        </div>
    </div>
    <?php if($isPlainUser): ?>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-wallet2"></i>
                </div>
                <div>
                    <div class="stat-value text-primary" style="font-size:18px;">Rp <?php echo e(number_format($currentMonthBudget, 0, ',', '.')); ?></div>
                    <div class="stat-label">Budget Bulan Ini</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon <?php echo e($currentMonthConsumed > $currentMonthBudget ? 'bg-danger' : 'bg-success'); ?> bg-opacity-10 <?php echo e($currentMonthConsumed > $currentMonthBudget ? 'text-danger' : 'text-success'); ?>">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                <div>
                    <div class="stat-value <?php echo e($currentMonthConsumed > $currentMonthBudget ? 'text-danger' : 'text-success'); ?>" style="font-size:18px;">Rp <?php echo e(number_format($currentMonthConsumed, 0, ',', '.')); ?></div>
                    <div class="stat-label">Terpakai Bulan Ini</div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="col-6 col-xl-3">
        <div class="card stat-card">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <div class="stat-value text-info"><?php echo e($stats['total_items']); ?></div>
                    <div class="stat-label">Total Item</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <a href="<?php echo e(route('admin.min-stock.index')); ?>" class="card stat-card text-decoration-none">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="stat-value text-warning"><?php echo e($stats['low_stock_items']); ?></div>
                    <div class="stat-label">Stok Menipis</div>
                </div>
            </div>
        </a>
    </div>
    <?php endif; ?>
</div>


<?php if(!empty($budgetHistory)): ?>
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-graph-up me-2 text-primary"></i>Budget vs Actual — 12 Bulan Terakhir</div>
    <div class="card-body">
        <div style="position:relative;height:280px;">
            <canvas id="budgetChart"></canvas>
        </div>
    </div>
</div>
<?php endif; ?>


<?php if(auth()->user()->isSuperAdmin() && $priceStats): ?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-danger px-2 py-1" style="font-size:11px;">SUPERADMIN</span>
            <span class="fw-semibold" style="font-size:15px;">Ringkasan Nilai Inventory & Transaksi</span>
            <?php if($priceStats['items_no_price'] > 0): ?>
            <a href="<?php echo e(route('master.items.index')); ?>" class="ms-auto badge bg-warning text-dark text-decoration-none" style="font-size:11px;">
                <i class="bi bi-exclamation-triangle me-1"></i><?php echo e($priceStats['items_no_price']); ?> item belum ada harga
            </a>
            <?php endif; ?>
        </div>
    </div>

    
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="border-left:4px solid #4680ff;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-database-fill-gear"></i>
                </div>
                <div>
                    <div class="stat-label">Nilai Inventory</div>
                    <div class="stat-value text-primary" style="font-size:20px;">
                        Rp <?php echo e(number_format($priceStats['total_inventory_value'], 0, ',', '.')); ?>

                    </div>
                    <div class="stat-change text-muted"><?php echo e($priceStats['items_with_price']); ?> item ada harga</div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="border-left:4px solid #dc3545;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-box-arrow-up-right"></i>
                </div>
                <div>
                    <div class="stat-label">Keluar Bulan Ini</div>
                    <div class="stat-value text-danger" style="font-size:20px;">
                        Rp <?php echo e(number_format($priceStats['goods_out_value'], 0, ',', '.')); ?>

                    </div>
                    <div class="stat-change text-muted"><?php echo e(now()->format('F Y')); ?></div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="border-left:4px solid #198754;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-box-arrow-in-down-right"></i>
                </div>
                <div>
                    <div class="stat-label">Masuk Bulan Ini</div>
                    <div class="stat-value text-success" style="font-size:20px;">
                        Rp <?php echo e(number_format($priceStats['goods_in_value'], 0, ',', '.')); ?>

                    </div>
                    <div class="stat-change text-muted"><?php echo e(now()->format('F Y')); ?></div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="col-6 col-xl-3">
        <div class="card stat-card" style="border-left:4px solid #0dcaf0;">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-arrow-return-left"></i>
                </div>
                <div>
                    <div class="stat-label">Dikembalikan</div>
                    <div class="stat-value text-info" style="font-size:20px;">
                        Rp <?php echo e(number_format($priceStats['return_value'], 0, ',', '.')); ?>

                    </div>
                    <div class="stat-change text-muted"><?php echo e(now()->format('F Y')); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up me-2 text-primary"></i>Nilai Transaksi <?php echo e(now()->year); ?> (Rp)</span>
            </div>
            <div class="card-body">
                <canvas id="valueChart" height="90"></canvas>
            </div>
        </div>
    </div>

    
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-trophy me-2 text-warning"></i>Top 5 Nilai Inventory
            </div>
            <div class="card-body p-0">
                <?php $__empty_1 = true; $__currentLoopData = $priceStats['top_items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $topItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="d-flex align-items-center gap-3 px-3 py-2 <?php echo e($loop->last ? '' : 'border-bottom'); ?>">
                    <div class="fw-bold text-muted" style="font-size:18px;width:24px;"><?php echo e($i + 1); ?></div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold text-truncate" style="font-size:13px;" title="<?php echo e($topItem['description']); ?>">
                            <?php echo e($topItem['description']); ?>

                        </div>
                        <div class="text-muted" style="font-size:11px;">
                            <?php echo e(number_format($topItem['qty'], 0)); ?> <?php echo e($topItem['unit']); ?>

                            × Rp <?php echo e(number_format($topItem['price'], 0, ',', '.')); ?>

                        </div>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold text-primary" style="font-size:13px;">
                            Rp <?php echo e(number_format($topItem['value'], 0, ',', '.')); ?>

                        </div>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center text-muted py-4" style="font-size:13px;">
                    Belum ada item dengan harga
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    
    <div class="col-lg-12">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart me-2 text-primary"></i>Grafik Transaksi <?php echo e(now()->year); ?></span>
            </div>
            <div class="card-body">
                <canvas id="transactionChart" height="90"></canvas>
            </div>
        </div>
    </div>
</div>


<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-clock-history me-2 text-primary"></i>Transaksi Terbaru</span>
        <a href="<?php echo e(route('transactions.goods-out.index')); ?>" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
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
                    <?php $__empty_1 = true; $__currentLoopData = $recentTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="fw-semibold text-primary"><?php echo e($trx->trans_no); ?></span></td>
                        <td>
                            <?php
                                $typeIcon = match($trx->trans_type) {
                                    'goods_out'    => 'bi-box-arrow-up-right text-danger',
                                    'goods_in'     => 'bi-box-arrow-in-down-right text-success',
                                    'memo_in'      => 'bi-journal-plus text-info',
                                    'memo_out'     => 'bi-journal-minus text-warning',
                                    'goods_return' => 'bi-arrow-return-left text-primary',
                                    default        => 'bi-arrow-left-right'
                                };
                            ?>
                            <i class="bi <?php echo e($typeIcon); ?> me-1"></i><?php echo e($trx->getTypeLabel()); ?>

                        </td>
                        <td><?php echo e($trx->user->name); ?></td>
                        <td><?php echo e($trx->department?->name ?? '-'); ?></td>
                        <td><span class="badge bg-secondary"><?php echo e($trx->details->count()); ?> item</span></td>
                        <td><?php echo e($trx->trans_date->format('d/m/Y')); ?></td>
                        <td>
                            <span class="badge bg-<?php echo e($trx->getStatusBadge()); ?>"><?php echo e($trx->getStatusLabel()); ?></span>
                        </td>
                        <td>
                            <a href="<?php echo e(route('admin.approvals.show', $trx->id)); ?>" class="btn btn-sm btn-light">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada transaksi</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
// Chart transaksi (jumlah)
const ctx = document.getElementById('transactionChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [
            {
                label: 'Keluar',
                data: <?php echo json_encode($chartOut->values()); ?>,
                backgroundColor: 'rgba(220,53,69,.7)',
                borderRadius: 4,
            },
            {
                label: 'Masuk',
                data: <?php echo json_encode($chartIn->values()); ?>,
                backgroundColor: 'rgba(70,128,255,.7)',
                borderRadius: 4,
            },
            {
                label: 'Kembali',
                data: <?php echo json_encode($chartReturn->values()); ?>,
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
<?php if(auth()->user()->isSuperAdmin() && $priceStats): ?>
const vCtx = document.getElementById('valueChart').getContext('2d');
new Chart(vCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chartLabels); ?>,
        datasets: [
            {
                label: 'Nilai Keluar (Rp)',
                data: <?php echo json_encode($priceStats['value_chart_out']); ?>,
                borderColor: 'rgba(220,53,69,1)',
                backgroundColor: 'rgba(220,53,69,.08)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
            },
            {
                label: 'Nilai Masuk (Rp)',
                data: <?php echo json_encode($priceStats['value_chart_in']); ?>,
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
<?php endif; ?>

<?php if(!empty($budgetHistory)): ?>
const budgetChartEl = document.getElementById('budgetChart');
if (budgetChartEl) {
    const rupiah = (v) => 'Rp ' + Number(v).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    new Chart(budgetChartEl, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(collect($budgetHistory)->pluck('label'), 15, 512) ?>,
            datasets: [
                {
                    type: 'line',
                    label: 'Budget',
                    data: <?php echo json_encode(collect($budgetHistory)->pluck('budget'), 15, 512) ?>,
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
                    data: <?php echo json_encode(collect($budgetHistory)->pluck('consumed'), 15, 512) ?>,
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
<?php endif; ?>
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/dashboard/index.blade.php ENDPATH**/ ?>