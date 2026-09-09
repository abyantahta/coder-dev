<?php $__env->startSection('title','Serah Terima Barang'); ?>
<?php $__env->startSection('page-title','Serah Terima Barang — Pilih Departemen'); ?>
<?php $__env->startSection('breadcrumb'); ?>
    <li class="breadcrumb-item">General Affair</li>
    <li class="breadcrumb-item active">Serah Terima Barang</li>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="row g-3">
    <?php $__empty_1 = true; $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
    <div class="col-md-4 col-lg-3">
        <a href="<?php echo e(route('ga.checkout.scan', ['department_id' => $dept->id])); ?>" class="text-decoration-none">
            <div class="card h-100 text-center py-4">
                <i class="bi bi-building fs-1 text-primary mb-2"></i>
                <div class="fw-semibold text-dark"><?php echo e($dept->name); ?></div>
                <div class="text-muted" style="font-size:12px;">
                    <?php echo e($dept->items_available); ?> item tersedia untuk diambil
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
    <div class="col-12 text-center text-muted py-5">Belum ada departemen aktif.</div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/warehouse/resources/views/procurement/ga/checkout/select-department.blade.php ENDPATH**/ ?>