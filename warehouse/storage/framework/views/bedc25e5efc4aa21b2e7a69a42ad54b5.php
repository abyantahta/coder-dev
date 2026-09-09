<?php $__env->startSection('title','Serah Terima Barang'); ?>
<?php $__env->startSection('page-title', 'Serah Terima: ' . $employee->name); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item"><a href="<?php echo e(route('ga.checkout.index')); ?>" class="text-decoration-none">Serah Terima Barang</a></li>
    <li class="breadcrumb-item active"><?php echo e($employee->name); ?></li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="card mb-3">
    <div class="card-body d-flex align-items-center gap-3">
        <img src="<?php echo e($employee->photo ? asset('storage/'.$employee->photo) : asset('assets/images/user/avatar-1.jpg')); ?>"
             class="rounded-circle" style="width:56px;height:56px;object-fit:cover;">
        <div>
            <div class="fw-bold"><?php echo e($employee->name); ?></div>
            <div class="text-muted" style="font-size:12px;"><?php echo e($employee->npk); ?> — <?php echo e($employee->department?->name); ?></div>
        </div>
    </div>
</div>

<form action="<?php echo e(route('ga.checkout.store', $employee->id)); ?>" method="POST">
    <?php echo csrf_field(); ?>
    <div class="card">
        <div class="card-header"><i class="bi bi-box-seam me-2 text-primary"></i>Item yang Bisa Diserahkan</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Item</th><th>Sisa Permintaan</th><th>Tersedia di Dept</th><th width="150">Qty Diserahkan</th></tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $lines; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?php echo e($row->detail->item->name); ?></div>
                                <div class="text-muted" style="font-size:11px;"><?php echo e($row->detail->item->item_code); ?></div>
                                <input type="hidden" name="items[<?php echo e($i); ?>][procurement_request_detail_id]" value="<?php echo e($row->detail->id); ?>">
                            </td>
                            <td><?php echo e(number_format($row->remaining, 2)); ?></td>
                            <td><?php echo e(number_format($row->available, 2)); ?></td>
                            <td>
                                <input type="number" step="0.01" min="0" max="<?php echo e($row->available); ?>"
                                       name="items[<?php echo e($i); ?>][qty]" class="form-control form-control-sm"
                                       value="<?php echo e($row->available); ?>">
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">Tidak ada barang yang bisa diserahkan untuk karyawan ini saat ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if($lines->count()): ?>
        <div class="card-footer bg-transparent d-flex gap-2">
            <button type="submit" class="btn btn-primary" onclick="return confirm('Konfirmasi serah terima barang ke <?php echo e($employee->name); ?>?')">
                <i class="bi bi-check-lg me-1"></i>Konfirmasi Serah Terima
            </button>
            <a href="<?php echo e(route('ga.checkout.index')); ?>" class="btn btn-outline-secondary">Batal</a>
        </div>
        <?php endif; ?>
    </div>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/procurement/ga/checkout/show.blade.php ENDPATH**/ ?>