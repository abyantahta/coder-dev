<?php $__env->startSection('title', 'Approval Transaksi'); ?>
<?php $__env->startSection('page-title', 'Approval Transaksi'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">Admin</li>
    <li class="breadcrumb-item active">Approval</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php if($pending->count()): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-clock-history fs-5"></i>
    <strong><?php echo e($pending->count()); ?> transaksi</strong>&nbsp;menunggu persetujuan Anda.
</div>
<?php endif; ?>


<div class="card mb-4">
    <div class="card-header d-flex justify-content-between">
        <span><i class="bi bi-clock me-2 text-warning"></i>Menunggu Approval</span>
        <span class="badge bg-warning text-dark"><?php echo e($pending->count()); ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Tipe</th>
                        <th>User / Dept</th>
                        <th>Tanggal</th>
                        <th>Item</th>
                        <th>Catatan</th>
                        <th width="140">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $pending; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="fw-semibold text-primary"><?php echo e($trx->trans_no); ?></span></td>
                        <td>
                            <span class="badge bg-<?php echo e($trx->trans_type === 'goods_out' ? 'danger' : 'success'); ?> bg-opacity-10
                                text-<?php echo e($trx->trans_type === 'goods_out' ? 'danger' : 'success'); ?>">
                                <?php echo e($trx->getTypeLabel()); ?>

                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold" style="font-size:13px;"><?php echo e($trx->user->name); ?></div>
                            <div class="text-muted" style="font-size:11px;"><?php echo e($trx->department?->name ?? '-'); ?></div>
                        </td>
                        <td><?php echo e($trx->trans_date->format('d/m/Y')); ?></td>
                        <td>
                            <?php $__currentLoopData = $trx->details->take(2); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $d): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div style="font-size:12px;"><?php echo e($d->item->description); ?>: <strong><?php echo e($d->qty_requested); ?> <?php echo e($d->item->unit); ?></strong></div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if($trx->details->count() > 2): ?>
                            <div class="text-muted" style="font-size:11px;">+<?php echo e($trx->details->count() - 2); ?> lainnya</div>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted" style="font-size:12px;"><?php echo e(Str::limit($trx->notes, 40) ?? '-'); ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo e(route('admin.approvals.show', $trx->id)); ?>"
                                   class="btn btn-sm btn-primary" title="Review">
                                    <i class="bi bi-check2-circle me-1"></i>Review
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-check-circle text-success" style="font-size:28px;"></i>
                        <p class="mt-2 mb-0">Tidak ada transaksi yang menunggu approval</p>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>


<div class="card">
    <div class="card-header"><i class="bi bi-clock-history me-2 text-primary"></i>Riwayat Approval</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="historyTable">
                <thead class="table-light">
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Tipe</th>
                        <th>User</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Diproses Oleh</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="fw-semibold text-primary"><?php echo e($trx->trans_no); ?></span></td>
                        <td><?php echo e($trx->getTypeLabel()); ?></td>
                        <td><?php echo e($trx->user->name); ?></td>
                        <td><?php echo e($trx->trans_date->format('d/m/Y')); ?></td>
                        <td><span class="badge bg-<?php echo e($trx->getStatusBadge()); ?>"><?php echo e($trx->getStatusLabel()); ?></span></td>
                        <td><?php echo e($trx->approver?->name ?? '-'); ?></td>
                        <td>
                            <a href="<?php echo e(route('admin.approvals.show', $trx->id)); ?>" class="btn btn-sm btn-light">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Belum ada riwayat</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
<?php if($history->count()): ?>
$('#historyTable').DataTable({
    pageLength: 10,
    language: { search: 'Cari:', paginate: { previous: '&laquo;', next: '&raquo;' }, info: 'Menampilkan _START_-_END_ dari _TOTAL_', zeroRecords: 'Tidak ada data' }
});
<?php endif; ?>
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/admin/approvals/index.blade.php ENDPATH**/ ?>