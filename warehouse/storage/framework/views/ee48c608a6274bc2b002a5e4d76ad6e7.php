<?php $__env->startSection('title','Terima Barang'); ?>
<?php $__env->startSection('page-title','Terima & Distribusi Barang'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">General Affair</li>
    <li class="breadcrumb-item active">Terima Barang</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <div class="card-header">
        <i class="bi bi-box-seam-fill me-2 text-primary"></i>PR Siap Diterima (sudah dikirim ke QAD)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>No. PR</th><th>No. Req QAD</th><th>Dibuat Oleh</th><th>Tanggal Kirim</th><th>Status</th><th width="120">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $purchaseRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="fw-semibold text-primary"><?php echo e($pr->pr_no); ?></span></td>
                        <td><?php echo e($pr->qad_req_no ?? '-'); ?></td>
                        <td><?php echo e($pr->creator->name); ?></td>
                        <td><?php echo e($pr->sent_to_qad_at?->format('d/m/Y H:i') ?? '-'); ?></td>
                        <td><span class="badge bg-<?php echo e($pr->getStatusBadge()); ?>"><?php echo e($pr->getStatusLabel()); ?></span></td>
                        <td>
                            <a href="<?php echo e(route('ga.receiving.create', $pr->id)); ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-box-arrow-in-down me-1"></i>Terima
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada PR yang menunggu diterima</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Penerimaan</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="30"></th>
                        <th>No. PR</th>
                        <th>No. PO (QAD)</th>
                        <th>Diterima Oleh</th>
                        <th>Tanggal Terima</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $recvTotal = $pr->details->sum(fn($d) => $d->qty_needed * ($d->procurementItem->price ?? 0)); ?>
                    <tr>
                        <td>
                            <button type="button" class="btn btn-sm btn-light" data-bs-toggle="collapse" data-bs-target="#recv-items-<?php echo e($pr->id); ?>">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </td>
                        <td><a href="<?php echo e(route('ga.requests.show', $pr->id)); ?>" class="fw-semibold text-primary text-decoration-none"><?php echo e($pr->pr_no); ?></a></td>
                        <td><?php echo e($pr->qad_po_no ?? '-'); ?></td>
                        <td><?php echo e($pr->receivedBy?->name ?? '-'); ?></td>
                        <td><?php echo e($pr->received_at?->format('d/m/Y H:i') ?? '-'); ?></td>
                        <td><span class="badge bg-<?php echo e($pr->getStatusBadge()); ?>"><?php echo e($pr->getStatusLabel()); ?></span></td>
                    </tr>
                    <tr class="collapse" id="recv-items-<?php echo e($pr->id); ?>">
                        <td></td>
                        <td colspan="5" class="bg-light">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr><th>Item</th><th>Qty</th><th>UOM</th><th>Harga Satuan</th><th>Subtotal</th><th>Budget Bulan Ini</th><th>Terpakai</th><th>Sisa</th></tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $pr->details; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $bi = $budgetInfo[$d->id] ?? null; ?>
                                    <tr>
                                        <td><?php echo e($d->procurementItem->name); ?> <span class="text-muted">(<?php echo e($d->procurementItem->item_code); ?>)</span></td>
                                        <td><?php echo e(number_format($d->qty_needed, 2)); ?></td>
                                        <td><?php echo e($d->procurementItem->uom->code); ?></td>
                                        <td>Rp <?php echo e(number_format($d->procurementItem->price ?? 0, 0, ',', '.')); ?></td>
                                        <td class="fw-semibold">Rp <?php echo e(number_format($d->qty_needed * ($d->procurementItem->price ?? 0), 0, ',', '.')); ?></td>
                                        <?php if($bi): ?>
                                        <td>Rp <?php echo e(number_format($bi['budget'], 0, ',', '.')); ?></td>
                                        <td>Rp <?php echo e(number_format($bi['consumed'], 0, ',', '.')); ?></td>
                                        <td class="fw-semibold <?php echo e($bi['remaining'] < 0 ? 'text-danger' : 'text-success'); ?>">Rp <?php echo e(number_format($bi['remaining'], 0, ',', '.')); ?></td>
                                        <?php else: ?>
                                        <td colspan="3" class="text-muted">-</td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Total</td>
                                        <td class="fw-bold text-success">Rp <?php echo e(number_format($recvTotal, 0, ',', '.')); ?></td>
                                        <td colspan="3"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat penerimaan</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3"><?php echo e($history->links('pagination::bootstrap-5')); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/procurement/ga/receiving/index.blade.php ENDPATH**/ ?>