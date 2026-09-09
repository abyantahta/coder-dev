<?php $__env->startSection('title','Permintaan Saya'); ?>
<?php $__env->startSection('page-title','Permintaan Pengadaan Saya'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">Pengadaan</li>
    <li class="breadcrumb-item active">Permintaan Saya</li>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-actions'); ?>
    <a href="<?php echo e(route('procurement.requests.create')); ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Buat Permintaan
    </a>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">-- Semua Status --</option>
                    <?php $__currentLoopData = $statusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($value); ?>" <?php echo e(request('status') === $value ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Tanggal Dari</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?php echo e(request('date_from')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold" style="font-size:12px;">Tanggal Sampai</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?php echo e(request('date_to')); ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="<?php echo e(route('procurement.requests.index')); ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="reqTable">
                <thead class="table-light">
                    <tr>
                        <th>No. Permintaan</th><th>Keperluan</th><th>Departemen</th>
                        <th>Item</th><th>No. PO</th><th>Tanggal</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $req): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td><span class="fw-semibold text-primary"><?php echo e($req->req_no); ?></span></td>
                        <td><?php echo e(Str::limit($req->purpose, 40)); ?></td>
                        <td><?php echo e($req->department?->name ?? '-'); ?></td>
                        <td><span class="badge bg-secondary"><?php echo e($req->details->count()); ?> item</span></td>
                        <td><?php echo e($req->aggregatedPr?->qad_po_no ?? '-'); ?></td>
                        <td><?php echo e($req->req_date->format('d/m/Y')); ?></td>
                        <td><span class="badge bg-<?php echo e($req->getStatusBadge()); ?>"><?php echo e($req->getStatusLabel()); ?></span></td>
                        <td>
                            <a href="<?php echo e(route('procurement.requests.show', $req->id)); ?>" class="btn btn-sm btn-light">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="bi bi-inbox" style="font-size:32px;"></i>
                            <p class="mt-2 mb-3">Belum ada permintaan pengadaan</p>
                            <a href="<?php echo e(route('procurement.requests.create')); ?>" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-lg me-1"></i>Buat Permintaan
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3"><?php echo e($requests->links('pagination::bootstrap-5')); ?></div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
<?php if($requests->count()): ?>
$('#reqTable').DataTable({ paging:false, info:false, order:[[5,'desc']], language:{ search:'Cari:' }, columnDefs:[{orderable:false,targets:[7]}] });
<?php endif; ?>
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/procurement/requests/index.blade.php ENDPATH**/ ?>